<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\View;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Models\Redirect;
use Dashed\DashedCore\Models\NotFoundPage;
use Dashed\DashedCore\Models\Customsetting;

class FrontendController extends Controller
{
    public static function pageNotFoundView()
    {
        seo()->metaData('metaTitle', 'Pagina niet gevonden');

        if (View::exists(config('dashed-core.site_theme') . '.not-found.show')) {
            return response()
                ->view(config('dashed-core.site_theme') . '.not-found.show')
                ->setStatusCode(404);
        }

        abort(404);
    }

    public function pageNotFound()
    {
        NotFoundPage::saveOccurrence(
            str(request()->fullUrl())->replace(request()->root(), ''),
            404,
            request()->header('referer'),
            request()->userAgent(),
            request()->ip(),
            Sites::getActive(),
            app()->getLocale()
        );

        return self::pageNotFoundView();
    }

    public function index($slug = null)
    {
        $originalSlug = $slug ?? '';

        // Media route hard skip
        if (str($slug)->contains('__media/')) {
            abort(404);
        }

        // Locale-prefix strippen (bijv. nl/slug, en/slug)
        $slug = $this->stripLocalePrefix($slug);

        // Twitter / default meta image – 1x settings ophalen
        $twitterSite = Customsetting::get('default_meta_data_twitter_site');
        if ($twitterSite) {
            seo()->metaData('twitterSite', $twitterSite);
            seo()->metaData('twitterCreator', $twitterSite);
        }

        $defaultMetaImage = Customsetting::get('default_meta_data_image');
        if ($defaultMetaImage) {
            // Let op: dit is bewust de "ruwe" waarde (id / referentie),
            // later wordt hij pas naar een echte URL omgezet.
            seo()->metaData('metaImage', $defaultMetaImage);
        }

        // Route models afhandelen
        foreach (cms()->builder('routeModels') as $routeModel) {
            $response = $this->resolveRouteModel($routeModel, $slug);

            // 1) Normale view-response
            if ($response instanceof \Illuminate\View\View) {
                $this->enrichSchemasWithPageMeta();

                if ($redirect = cms()->checkModelPassword()) {
                    return $redirect;
                }

                // Laat Laravel zelf de view -> Response conversion doen
                return $response;
            }

            // 2) "pageNotFound" signaal
            if ($response === 'pageNotFound') {
                if ($redirectResponse = $this->findRedirectResponse($slug, $originalSlug)) {
                    return $redirectResponse;
                }

                return $this->$response();
            }

            // 3) Livewire component response
            if (is_array($response) && isset($response['livewireComponent'])) {
                if ($redirect = cms()->checkModelPassword()) {
                    return $redirect;
                }

                return view('dashed-core::layouts.livewire-master', [
                    'livewireComponent' => $response['livewireComponent'],
                    'parameters' => $response['parameters'] ?? [],
                ]);
            }
        }

        // Geen route match – alsnog redirect proberen
        if ($redirectResponse = $this->findRedirectResponse($slug, $originalSlug)) {
            return $redirectResponse;
        }

        return $this->pageNotFound();
    }

    /**
     * Haalt locale-prefix (nl/, en/, etc.) van de slug af.
     */
    protected function stripLocalePrefix(?string $slug): string
    {
        $slug = $slug ?? '';

        if ($slug === '') {
            return '';
        }

        foreach (Locales::getLocales() as $locale) {
            $localeId = $locale['id'] ?? null;

            if (! $localeId) {
                continue;
            }

            if ($slug === $localeId) {
                return '';
            }

            $prefix = $localeId . '/';

            if (Str::startsWith($slug, $prefix)) {
                return Str::substr($slug, strlen($prefix));
            }
        }

        return $slug;
    }

    /**
     * Resolve een route model uit cms()->builder('routeModels').
     */
    protected function resolveRouteModel(array $routeModel, string $slug)
    {
        if (isset($routeModel['class']) && method_exists($routeModel['class'], 'resolveRoute')) {
            return $routeModel['class']::resolveRoute([
                'slug' => $slug,
            ]);
        }

        return $routeModel['routeHandler']::handle([
            'slug' => $slug,
        ]);
    }

    /**
     * LocalBusiness schema verrijken met paginatitel + meta image (indien aanwezig).
     */
    protected function enrichSchemasWithPageMeta(): void
    {
        $schemas = seo()->metaData('schemas');

        if (isset($schemas['localBusiness'])) {
            // Naam updaten naar actuele paginatitel
            $schemas['localBusiness']->name(seo()->metaData('metaTitle'));

            // Meta image (indien numeric id / referentie) -> URL resolven en voor zowel schema als seo opslaan
            if ($metaImage = seo()->metaData('metaImage')) {
                $media = mediaHelper()->getSingleMedia($metaImage, 'huge');

                if ($media && $media->url) {
                    $schemas['localBusiness']->image($media->url);
                    seo()->metaData('metaImage', $media->url);
                }
            }
        }

        seo()->metaData('schemas', $schemas);
    }

    /**
     * Vindt een redirect voor slug / originalSlug en geeft direct een RedirectResponse terug,
     * of null als er niks gevonden wordt.
     */
    protected function findRedirectResponse(string $slug, string $originalSlug)
    {
        $candidates = $this->buildRedirectCandidates($slug, $originalSlug);

        if (empty($candidates)) {
            return null;
        }

        $redirect = Redirect::query()
            ->whereIn('from', $candidates)
            ->first();

        if (! $redirect) {
            return null;
        }

        return redirect($redirect->to, $redirect->sort);
    }

    /**
     * Maakt alle mogelijke "from" varianten voor zowel slug als originalSlug.
     */
    protected function buildRedirectCandidates(string $slug, string $originalSlug): array
    {
        $candidates = [];

        foreach ([$slug, $originalSlug] as $s) {
            $s = trim($s ?? '', '/');

            if ($s === '') {
                continue;
            }

            $candidates[] = $s;
            $candidates[] = '/' . $s;
            $candidates[] = $s . '/';
            $candidates[] = '/' . $s . '/';
        }

        // Duplicaten eruit
        return array_values(array_unique($candidates));
    }
}
