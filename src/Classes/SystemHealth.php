<?php

namespace Dashed\DashedCore\Classes;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedCore\Jobs\QueueHeartbeatJob;

/**
 * Wat er op deze installatie stil kan falen zonder dat iemand het merkt: een
 * scheduler die niet draait, een wachtrij die stilstaat, migraties die na een
 * `composer update` zijn blijven liggen, en productie-instellingen die nog op
 * test staan. Offertes, betaalherinneringen, Bol-retouren, webhooks en de
 * nachtronde leunen allemaal op `schedule:run`; draait die niet, dan gebeurt er
 * gewoon niets.
 *
 * `issues()` levert alleen wat mis is (voor de balk bovenin het paneel),
 * `run()` elke controle met zijn stand (voor de pagina Systeemstatus).
 * Pakketten voegen controles toe met `extend()`, zoals de betaalproviders hun
 * testmodus.
 */
class SystemHealth
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const DANGER = 'danger';

    public const SCHEDULER_KEY = 'dashed:system-health:scheduler-beat';

    public const QUEUE_KEY = 'dashed:system-health:queue-beat';

    public const WATCH_KEY = 'dashed:system-health:watch-start';

    public const MIGRATIONS_KEY = 'dashed:system-health:pending-migrations';

    /** Zo lang mag de scheduler stil zijn voordat het een probleem is. */
    public const SCHEDULER_STALE_MINUTES = 10;

    /** De wachtrij krijgt elke vijf minuten een hartslag; drie gemiste zijn stilstand. */
    public const QUEUE_STALE_MINUTES = 15;

    /** Zo lang mag een job in de wachtrij wachten voordat die achterloopt. */
    public const QUEUE_LAG_MINUTES = 10;

    /** @var array<int|string, callable(): array<int, array{key: string, label: string, status: string, detail: string}>> */
    protected static array $extensions = [];

    /**
     * Een pakket voegt eigen controles toe. De closure loopt pas als de balk
     * of de pagina gerenderd wordt en geeft een lijst items terug (mag leeg).
     * Met een naam overschrijft een tweede aanmelding de eerste: de statische
     * lijst overleeft de herstart van de applicatie tussen twee tests.
     */
    public static function extend(callable $items, ?string $name = null): void
    {
        if ($name === null) {
            self::$extensions[] = $items;
        } else {
            self::$extensions[$name] = $items;
        }
    }

    public static function flushExtensions(): void
    {
        self::$extensions = [];
    }

    /**
     * Alleen de controles die niet in orde zijn.
     *
     * @return array<int, array{key: string, label: string, status: string, detail: string}>
     */
    public static function issues(?Request $request = null): array
    {
        return array_values(array_filter(
            self::run($request),
            fn (array $item) => $item['status'] !== self::OK,
        ));
    }

    /**
     * @return array<int, array{key: string, label: string, status: string, detail: string}>
     */
    public static function run(?Request $request = null): array
    {
        $request ??= request();

        $items = [
            ...self::schedulerItems(),
            self::migrationsItem(),
            ...self::productionItems(),
            self::appUrlItem($request),
        ];

        foreach (self::$extensions as $extension) {
            $extra = rescue(fn () => $extension(), [], false);
            foreach ((array) $extra as $item) {
                if (is_array($item) && isset($item['key'], $item['label'], $item['status'])) {
                    $items[] = self::item($item['key'], $item['label'], $item['status'], $item['detail'] ?? '');
                }
            }
        }

        return array_values(array_filter($items));
    }

    /**
     * Aangeroepen door de scheduler, elke minuut.
     */
    public static function beatScheduler(): void
    {
        Cache::forever(self::SCHEDULER_KEY, now()->getTimestamp());
    }

    /**
     * Aangeroepen door de scheduler, elke vijf minuten: een job die bij
     * uitvoering zijn eigen wachttijd vastlegt.
     */
    public static function dispatchQueueBeat(): void
    {
        if (config('queue.default') === 'sync') {
            return;
        }

        dispatch(new QueueHeartbeatJob(now()->getTimestamp()));
    }

    public static function beatQueue(int $dispatchedAt): void
    {
        $now = now()->getTimestamp();

        Cache::forever(self::QUEUE_KEY, [
            'handled_at' => $now,
            'lag' => max(0, $now - $dispatchedAt),
        ]);
    }

    public static function forgetMigrations(): void
    {
        Cache::forget(self::MIGRATIONS_KEY);
    }

    /**
     * Scheduler en wachtrij. Lokaal draait er meestal geen scheduler en dat is
     * geen fout, dus daar zwijgen deze twee.
     *
     * @return array<int, array{key: string, label: string, status: string, detail: string}>
     */
    protected static function schedulerItems(): array
    {
        if (app()->isLocal()) {
            return [];
        }

        // Pas tellen vanaf het moment dat we kijken: direct na een uitrol is er
        // nog geen hartslag, en dat is nog geen stilstand.
        Cache::add(self::WATCH_KEY, now()->getTimestamp(), now()->addYear());
        $watchingSince = (int) Cache::get(self::WATCH_KEY);
        $graceOver = now()->getTimestamp() - $watchingSince > self::SCHEDULER_STALE_MINUTES * 60;

        $items = [];

        $beat = Cache::get(self::SCHEDULER_KEY);
        $schedulerStale = $beat
            ? now()->getTimestamp() - (int) $beat > self::SCHEDULER_STALE_MINUTES * 60
            : $graceOver;

        $items[] = self::item(
            'scheduler',
            __('Scheduler'),
            $schedulerStale ? self::DANGER : self::OK,
            match (true) {
                ! $schedulerStale && $beat => __('Laatst gedraaid :tijd.', ['tijd' => Carbon::createFromTimestamp((int) $beat)->diffForHumans()]),
                ! $schedulerStale => __('Nog geen hartslag gezien, er wordt gekeken.'),
                (bool) $beat => __('schedule:run heeft :tijd voor het laatst gedraaid. Offertes, herinneringen, Bol-retouren, webhooks en de nachtronde staan stil. Controleer de cron op de server.', ['tijd' => Carbon::createFromTimestamp((int) $beat)->diffForHumans()]),
                default => __('schedule:run draait niet op deze server. Offertes, herinneringen, Bol-retouren, webhooks en de nachtronde staan stil. Zet de cron "* * * * * php artisan schedule:run" op de server.'),
            },
        );

        // Zonder scheduler komt er ook geen hartslag in de wachtrij; dat is dan
        // geen tweede fout om te melden.
        if (config('queue.default') === 'sync' || $schedulerStale) {
            return $items;
        }

        $queue = Cache::get(self::QUEUE_KEY);
        $handledAt = is_array($queue) ? (int) ($queue['handled_at'] ?? 0) : 0;
        $lag = is_array($queue) ? (int) ($queue['lag'] ?? 0) : 0;

        $queueStale = $handledAt
            ? now()->getTimestamp() - $handledAt > self::QUEUE_STALE_MINUTES * 60
            : now()->getTimestamp() - $watchingSince > self::QUEUE_STALE_MINUTES * 60;

        if ($queueStale) {
            $items[] = self::item(
                'queue',
                __('Wachtrij'),
                self::DANGER,
                $handledAt
                    ? __('De wachtrij heeft :tijd voor het laatst een job verwerkt. Mails, exports en koppelingen blijven liggen. Controleer de queue-worker of Horizon.', ['tijd' => Carbon::createFromTimestamp($handledAt)->diffForHumans()])
                    : __('De wachtrij verwerkt geen jobs. Mails, exports en koppelingen blijven liggen. Start een queue-worker of Horizon.'),
            );
        } elseif ($lag > self::QUEUE_LAG_MINUTES * 60) {
            $items[] = self::item(
                'queue',
                __('Wachtrij'),
                self::WARNING,
                __('De wachtrij loopt :minuten minuten achter. Er staat meer werk klaar dan de worker aankan.', ['minuten' => (int) round($lag / 60)]),
            );
        } else {
            $items[] = self::item(
                'queue',
                __('Wachtrij'),
                self::OK,
                $handledAt ? __('Laatst verwerkt :tijd.', ['tijd' => Carbon::createFromTimestamp($handledAt)->diffForHumans()]) : __('Nog geen hartslag gezien, er wordt gekeken.'),
            );
        }

        return $items;
    }

    /**
     * Openstaande migraties. Het lezen van alle migratiebestanden is te zwaar
     * voor elke paginalading, dus de uitkomst staat tien minuten in de cache
     * en wordt na elke `migrate` meteen weggegooid.
     *
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected static function migrationsItem(): array
    {
        $pending = Cache::remember(self::MIGRATIONS_KEY, now()->addMinutes(10), fn () => self::pendingMigrations());

        if ($pending === null) {
            return self::item('migrations', __('Migraties'), self::OK, __('Kon de migraties niet lezen.'));
        }

        if ($pending === []) {
            return self::item('migrations', __('Migraties'), self::OK, __('Alle migraties zijn gedraaid.'));
        }

        $shown = implode(', ', array_slice($pending, 0, 3));

        return self::item(
            'migrations',
            __('Migraties'),
            self::DANGER,
            trans_choice(':aantal migratie staat nog open (:namen). Draai php artisan migrate op de server.|:aantal migraties staan nog open (:namen). Draai php artisan migrate op de server.', count($pending), [
                'aantal' => count($pending),
                'namen' => $shown.(count($pending) > 3 ? ', ...' : ''),
            ]),
        );
    }

    /**
     * @return array<int, string>|null
     */
    public static function pendingMigrations(): ?array
    {
        try {
            $migrator = app('migrator');

            if (! $migrator->repositoryExists()) {
                return null;
            }

            $files = $migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths()));
            $ran = $migrator->getRepository()->getRan();

            return array_values(array_diff(array_keys($files), $ran));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Instellingen die op productie nooit zo horen te staan.
     *
     * @return array<int, array{key: string, label: string, status: string, detail: string}>
     */
    protected static function productionItems(): array
    {
        if (! app()->isProduction()) {
            return [];
        }

        $mailer = (string) config('mail.default');
        $debug = (bool) config('app.debug');

        return [
            self::item(
                'mailer',
                __('Mailer'),
                in_array($mailer, ['log', 'array'], true) ? self::DANGER : self::OK,
                in_array($mailer, ['log', 'array'], true)
                    ? __('MAIL_MAILER staat op :mailer: er gaat geen enkele mail de deur uit, ook geen orderbevestiging.', ['mailer' => $mailer])
                    : __('MAIL_MAILER staat op :mailer.', ['mailer' => $mailer]),
            ),
            self::item(
                'app_debug',
                __('Debug-modus'),
                $debug ? self::DANGER : self::OK,
                $debug
                    ? __('APP_DEBUG staat aan op productie. Elke foutpagina laat configuratie en code zien.')
                    : __('APP_DEBUG staat uit.'),
            ),
        ];
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}|null
     */
    protected static function appUrlItem(Request $request): ?array
    {
        $configured = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $actual = strtolower((string) $request->getHost());

        if ($configured === '' || $actual === '' || app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        // De panelhost mag ook een van de sites zijn; alleen een host die
        // nergens bij hoort is een afwijking.
        $known = [$configured, ...array_map(
            fn ($site) => strtolower((string) parse_url(Sites::rootUrl($site['id'] ?? null), PHP_URL_HOST)),
            rescue(fn () => Sites::getSites(), [], false) ?: [],
        )];

        if (in_array($actual, $known, true)) {
            return self::item('app_url', __('APP_URL'), self::OK, __('APP_URL hoort bij dit domein.'));
        }

        return self::item(
            'app_url',
            __('APP_URL'),
            self::WARNING,
            __('Het paneel draait op :actual, maar APP_URL wijst naar :configured. Links in mails, ondertekende links en de vertrouwde hosts gaan dan van het verkeerde domein uit.', [
                'actual' => $actual,
                'configured' => $configured,
            ]),
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    public static function item(string $key, string $label, string $status, string $detail): array
    {
        return compact('key', 'label', 'status', 'detail');
    }
}
