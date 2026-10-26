<?php

namespace Dashed\DashedCore\Classes\Trash;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Actions\Action as NotificationAction;

/**
 * "Ongedaan maken" na verwijderen. Alles wat een beheerder in één verzoek naar
 * de prullenbak stuurt (één record, of tweehonderd met een bulkactie) komt in
 * één partij met één melding; de knop zet de hele partij terug. De partij leeft
 * een uur in de cache en hoort bij de gebruiker die hem maakte.
 */
class TrashUndo
{
    public const TTL_MINUTES = 60;

    protected static ?string $token = null;

    public static function deleted(Model $model): void
    {
        $user = auth()->user();

        if (! $user || (app()->runningInConsole() && ! app()->runningUnitTests()) || ! Trash::handles($model) || ! method_exists($model, 'trashed') || ! $model->trashed()) {
            return;
        }

        // Alleen in het paneel: een klant die op de site iets weggooit krijgt
        // geen beheermelding.
        if (! in_array($user->role ?? null, ['admin', 'superadmin'], true) && ! (method_exists($user, 'roles') && $user->roles()->exists())) {
            return;
        }

        self::$token ??= Str::random(32);
        $key = self::key(self::$token);

        $batch = Cache::get($key, ['user_id' => $user->getKey(), 'items' => []]);
        $batch['items'][] = [$model::class, $model->getKey()];
        Cache::put($key, $batch, now()->addMinutes(self::TTL_MINUTES));

        $count = count($batch['items']);

        // Eén melding per partij: de vorige versie (met een lager aantal) eruit.
        session()->put('filament.notifications', collect(session('filament.notifications', []))
            ->reject(fn ($notification) => ($notification['id'] ?? null) === 'dashed-trash-undo')
            ->values()
            ->all());

        Notification::make('dashed-trash-undo')
            ->title($count === 1
                ? __(':item staat in de prullenbak', ['item' => Trash::title($model)])
                : __(':aantal items staan in de prullenbak', ['aantal' => $count]))
            ->success()
            ->persistent()
            ->actions([
                NotificationAction::make('undo')
                    ->label(__('Ongedaan maken'))
                    ->url(route('filament.dashed.trash.undo', ['token' => self::$token])),
            ])
            ->send();
    }

    /**
     * @return array<int, Model> de teruggezette records
     */
    public static function undo(string $token, int|string $userId): array
    {
        $batch = Cache::pull(self::key($token));

        if (! is_array($batch) || (string) ($batch['user_id'] ?? '') !== (string) $userId) {
            return [];
        }

        $restored = [];
        foreach ($batch['items'] as [$type, $id]) {
            if ($model = Trash::restore($type, $id)) {
                $restored[] = $model;
            }
        }

        return $restored;
    }

    public static function reset(): void
    {
        self::$token = null;
    }

    protected static function key(string $token): string
    {
        return 'dashed:trash-undo:'.$token;
    }
}
