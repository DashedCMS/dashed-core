<?php

namespace Dashed\DashedCore\Classes\ErrorPages;

use Throwable;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Dashed\DashedCore\Mail\MailBranding;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Dashed\DashedCore\Middleware\FrontendMiddleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Rendert elke foutpagina (404, 403, 419, 429, 500, 503, ...) in de stijl van
 * de site in plaats van Laravels kale standaardpagina. Hangt als renderable aan
 * de exception handler (zie DashedCoreServiceProvider).
 *
 * Volgorde per verzoek, de eerste die bestaat én rendert wint:
 *  1. een eigen themaview in het project: <thema>.errors.<code>, dan <thema>.errors.show
 *     (en voor een 404 ook <thema>.not-found.show);
 *  2. dashed-core::errors.themed, in de <x-master> van het project;
 *  3. dashed-core::errors.standalone, een op zichzelf staande pagina met alleen
 *     sitenaam, logo en huisstijlkleur, zonder enige afhankelijkheid van de
 *     database of de layout van het project.
 *
 * Een verzoek aan het beheer (en een 503 bij onderhoud) slaat 1 en 2 over.
 * Mislukt een render (de database ligt eruit, de layout zelf is kapot), dan
 * valt hij door naar de volgende; mislukt alles, dan krijgt Laravel hem terug.
 */
class ErrorPageRenderer
{
    public const MARKER = 'data-dashed-error-page';

    public function render(Throwable $e, Request $request): ?Response
    {
        try {
            return $this->tryRender($e, $request);
        } catch (Throwable) {
            return null;
        }
    }

    protected function tryRender(Throwable $e, Request $request): ?Response
    {
        if ($this->leaveToLaravel($e, $request)) {
            return null;
        }

        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

        if ($status < 400) {
            return null;
        }

        $isCms = $this->isCmsRequest($request);
        $data = $this->viewData($e, $status, $isCms);

        foreach ($this->candidateViews($status, $isCms) as $view) {
            if (! rescue(fn () => View::exists($view), false, false)) {
                continue;
            }

            try {
                if ($view !== 'dashed-core::errors.standalone') {
                    $this->shareFrontendVariables($request);
                }

                return response(view($view, $data)->render(), $status, $headers);
            } catch (Throwable $renderFailure) {
                // Bij een 500 is de oorzaak al gemeld en is dit vrijwel zeker
                // dezelfde storing; bij een gezonde app is een kapotte
                // foutpagina wél iets om te weten.
                if ($e instanceof HttpExceptionInterface) {
                    rescue(fn () => report($renderFailure), null, false);
                }
            }
        }

        return null;
    }

    protected function leaveToLaravel(Throwable $e, Request $request): bool
    {
        if ($request->expectsJson()) {
            return true;
        }

        // Deze drie hebben een eigen afhandeling in Laravels handler (redirect
        // naar login, terug met validatiefouten, een meegegeven response).
        if ($e instanceof AuthenticationException || $e instanceof ValidationException || $e instanceof HttpResponseException) {
            return true;
        }

        // Een echte fout in debug-modus hoort de ontwikkelaar met stacktrace te zien.
        if (! $e instanceof HttpExceptionInterface && config('app.debug')) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function candidateViews(int $status, bool $isCms): array
    {
        $theme = config('dashed-core.site_theme', 'dashed');
        $views = [];

        if (! $isCms) {
            $views[] = "{$theme}.errors.{$status}";

            // Bij onderhoud is de database mogelijk halverwege een migratie;
            // een uitdrukkelijke 503-view van het project mag, de rest niet.
            if ($status !== 503) {
                $views[] = "{$theme}.errors.show";

                if ($status === 404) {
                    $views[] = "{$theme}.not-found.show";
                }

                $views[] = 'dashed-core::errors.themed';
            }
        }

        $views[] = 'dashed-core::errors.standalone';

        return $views;
    }

    /**
     * De <x-master> van een project leunt op wat FrontendMiddleware deelt
     * ($logo, $favicon, $siteName, $trackingSettings, ...). Een fout die vóór
     * die middleware ontstaat (een 419 uit de CSRF-controle, een route buiten
     * de frontend-groep) heeft die variabelen nog niet; dan draaien we de
     * middleware alsnog, precies zoals een gewone pagina hem zou krijgen.
     */
    protected function shareFrontendVariables(Request $request): void
    {
        if (array_key_exists('favicon', View::getShared())) {
            return;
        }

        app(FrontendMiddleware::class)->handle($request, fn () => null);
    }

    protected function isCmsRequest(Request $request): bool
    {
        if (rescue(fn () => cms()->isCMSRoute(), false, false)) {
            return true;
        }

        $path = trim((string) config('dashed-core.dashed_cms.path', 'dashed'), '/');
        if ($path === '') {
            return false;
        }

        if ($request->is($path, $path . '/*')) {
            return true;
        }

        // Een Livewire-verzoek vanuit het beheer heeft als pad livewire/update;
        // alleen de Referer zegt waar hij vandaan komt.
        if ($request->is('livewire/*')) {
            $refererPath = trim((string) parse_url((string) $request->headers->get('referer'), PHP_URL_PATH), '/');

            return $refererPath === $path || str_starts_with($refererPath, $path . '/');
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewData(Throwable $e, int $status, bool $isCms): array
    {
        return [
            'statusCode' => $status,
            'title' => ErrorPageTexts::title($status),
            'message' => $this->messageFor($e, $status),
            'exception' => $e,
            'errors' => new ViewErrorBag(),
            'branding' => $this->branding(),
            'isCms' => $isCms,
            'homeUrl' => rescue(fn () => Sites::url('/'), fn () => url('/'), false),
            'cmsUrl' => $isCms ? rescue(fn () => Filament::getPanel('dashed')->getUrl(), null, false) : null,
        ];
    }

    /**
     * Net als Laravels eigen 403- en 503-pagina: een uitdrukkelijk meegegeven
     * tekst (abort(403, 'Je adres staat niet op de lijst')) gaat voor de
     * standaardtekst. Bij andere codes is de melding technisch ("CSRF token
     * mismatch", "No query results for model") en hoort hij niet op het scherm.
     */
    protected function messageFor(Throwable $e, int $status): string
    {
        if ($e instanceof HttpExceptionInterface && in_array($status, [403, 503], true) && trim($e->getMessage()) !== '') {
            return $e->getMessage();
        }

        return ErrorPageTexts::message($status);
    }

    /**
     * Sitenaam, logo en huisstijlkleur, elk los in een rescue(): deze pagina
     * wordt ook getoond als de database niet bereikbaar is, en dan moet er nog
     * steeds iets herkenbaars op het scherm komen.
     *
     * @return array{siteName: string, logoUrl: ?string, primaryColor: string}
     */
    public function branding(): array
    {
        $siteName = rescue(fn () => (string) Customsetting::get('site_name'), '', false);
        $logoUrl = rescue(function () {
            $logoId = Customsetting::get('site_logo');

            return $logoId ? (mediaHelper()->getSingleMedia($logoId)->url ?? null) : null;
        }, null, false);
        $primaryColor = rescue(fn () => (string) Customsetting::get('mail_primary_color'), '', false);

        return [
            'siteName' => $siteName !== '' ? $siteName : (string) config('app.name', ''),
            'logoUrl' => $logoUrl ?: null,
            'primaryColor' => preg_match('/^#[0-9a-f]{3,8}$/i', $primaryColor) ? $primaryColor : MailBranding::DEFAULT_PRIMARY_COLOR,
        ];
    }
}
