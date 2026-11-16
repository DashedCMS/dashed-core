<?php

namespace Dashed\DashedCore\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Models\Redirect;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dashed\DashedCore\Classes\Redirects\RedirectChain;

/**
 * Een verwijderde pagina krijgt per taal een 301 naar een opvolger, of anders een 410 (bewust weg). Zonder dit
 * gaf elk verwijderd product een 404 en bleef Google het adres maanden opnieuw proberen.
 *
 * De opvolger komt van het model zelf (methode redirectTargetAfterDelete(string $locale): ?string) of van een
 * klasse in config('dashed-core.redirects.after_delete') per modelklasse, met __invoke(Model $model, string $locale).
 */
class RedirectDeletedModelUrlsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 60;

    /**
     * @param  array<string, string>  $urls  taal => pad zoals de pagina het had
     */
    public function __construct(
        public string $modelClass,
        public int|string $modelId,
        public array $urls,
    ) {
    }

    public function handle(): void
    {
        $model = $this->findModel();

        // Teruggezet voordat de taak liep: niets doen.
        if ($model && ! (method_exists($model, 'trashed') && $model->trashed())) {
            return;
        }

        foreach ($this->urls as $locale => $url) {
            $from = RedirectChain::normalize($url);

            if ($from === '/' || Redirect::findForSlugs([$from])) {
                continue;
            }

            $target = $model ? self::targetFor($model, (string) $locale) : null;

            if ($target && RedirectChain::normalize($target) !== $from) {
                $redirect = new Redirect();
                $redirect->from = $from;
                $redirect->to = $target;
                $redirect->sort = 301;

                if ($redirect->save()) {
                    continue;
                }
            }

            Redirect::markGone($from);
        }
    }

    public static function targetFor(Model $model, string $locale): ?string
    {
        try {
            if (method_exists($model, 'redirectTargetAfterDelete')) {
                return $model->redirectTargetAfterDelete($locale);
            }

            foreach ((array) config('dashed-core.redirects.after_delete', []) as $class => $resolver) {
                if ($model instanceof $class && class_exists($resolver)) {
                    return app($resolver)($model, $locale);
                }
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return null;
    }

    private function findModel(): ?Model
    {
        if (! class_exists($this->modelClass)) {
            return null;
        }

        $query = $this->modelClass::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($this->modelClass), true)) {
            $query->withTrashed();
        }

        return $query->find($this->modelId);
    }
}
