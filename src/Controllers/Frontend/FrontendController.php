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
            return response()->view(config('dashed-core.site_theme') . '.not-found.show')->setStatusCode(404);
        } else {
            abort(404);
        }
    }

    public function pageNotFound()
    {
        NotFoundPage::saveOccurrence(str(request()->fullUrl())->replace(request()->root(), ''), 404, request()->header('referer'), request()->userAgent(), request()->ip(), Sites::getActive(), app()->getLocale());

        return self::pageNotFoundView();
    }

    public function index($slug = null)
    {
        $originalSlug = $slug;

        if (str($slug)->contains('__media/')) {
            abort(404);
        }

        foreach (Locales::getLocales() as $locale) {
            if (Str::startsWith($slug, $locale['id'] . '/') || $slug == $locale['id']) {
                $slug = Str::substr($slug, strlen($locale['id']) + 1);
            }
        }

        seo()->metaData('twitterSite', Customsetting::get('default_meta_data_twitter_site'));
        seo()->metaData('twitterCreator', Customsetting::get('default_meta_data_twitter_site'));
        if (Customsetting::get('default_meta_data_image')) {
            seo()->metaData('metaImage', Customsetting::get('default_meta_data_image'));
        }

        foreach (cms()->builder('routeModels') as $routeModel) {
            if (method_exists($routeModel['class'], 'resolveRoute')) {
                $response = $routeModel['class']::resolveRoute([
                    'slug' => $slug,
                ]);
            } else {
                $response = $routeModel['routeHandler']::handle([
                    'slug' => $slug,
                ]);
            }

            if (is_a($response, \Illuminate\View\View::class)) {
                $schemas = seo()->metaData('schemas');
                $schemas['localBusiness']->name(seo()->metaData('metaTitle'));

                if (seo()->metaData('metaImage')) {
                    $schemas['localBusiness']->image(mediaHelper()->getSingleMedia(seo()->metaData('metaImage'), 'huge')->url ?? '');
                    seo()->metaData('metaImage', mediaHelper()->getSingleMedia(seo()->metaData('metaImage'), 'huge')->url ?? '');
                }
                seo()->metaData('schemas', $schemas);

                if ($redirect = cms()->checkModelPassword()) {
                    return $redirect;
                }

                return $response->render();
            } elseif ($response == 'pageNotFound') {

                if ($redirect = Redirect::where('from', $slug)->orWhere('from', '/' . $slug)->orWhere('from', $slug . '/')->orWhere('from', '/' . $slug . '/')->first()) {
                    return redirect($redirect->to, $redirect->sort);
                } elseif ($redirect = Redirect::where('from', $originalSlug)->orWhere('from', '/' . $originalSlug)->orWhere('from', $originalSlug . '/')->orWhere('from', '/' . $originalSlug . '/')->first()) {
                    return redirect($redirect->to, $redirect->sort);
                }

                return $this->$response();
            } elseif (is_array($response) && isset($response['livewireComponent'])) {

                if ($redirect = cms()->checkModelPassword()) {
                    return $redirect;
                }

                return view('dashed-core::layouts.livewire-master', [
                    'livewireComponent' => $response['livewireComponent'],
                    'parameters' => $response['parameters'] ?? [],
                ]);
            }
        }

        if ($redirect = Redirect::where('from', $slug)->orWhere('from', '/' . $slug)->orWhere('from', $slug . '/')->orWhere('from', '/' . $slug . '/')->first()) {
            return redirect($redirect->to, $redirect->sort);
        } elseif ($redirect = Redirect::where('from', $originalSlug)->orWhere('from', '/' . $originalSlug)->orWhere('from', $originalSlug . '/')->orWhere('from', '/' . $originalSlug . '/')->first()) {
            return redirect($redirect->to, $redirect->sort);
        }

        return $this->pageNotFound();
    }
}
