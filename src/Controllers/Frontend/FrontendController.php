<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Illuminate\Support\Str;
use Dashed\Drift\UrlBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Models\Redirect;
use Dashed\DashedCore\Models\Customsetting;

class FrontendController extends Controller
{
    public function pageNotFound()
    {
        seo()->metaData('metaTitle', 'Pagina niet gevonden');

        if (View::exists('dashed.not-found.show')) {
            return response()->view('dashed.not-found.show')->setStatusCode(404);
        } else {
            abort(404);
        }
    }

    public function index($slug = null)
    {
        if (str($slug)->startsWith('qcommerce') && config('filament.path') != 'qcommerce') {
            return redirect(config('filament.path'));
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
                    $schemas['localBusiness']->image(app(UrlBuilder::class)->url('dashed', seo()->metaData('metaImage'), [
                        'widen' => 1200,
                    ]));
                    seo()->metaData('metaImage', app(UrlBuilder::class)->url('dashed', seo()->metaData('metaImage'), [
                        'widen' => 1200,
                    ]));
                }
                seo()->metaData('schemas', $schemas);

                return $response->render();
            } elseif ($response == 'pageNotFound') {
                return $this->$response();
            }
        }

        if ($redirect = Redirect::where('from', $slug)->orWhere('from', '/' . $slug)->orWhere('from', $slug . '/')->orWhere('from', '/' . $slug . '/')->first()) {
            return redirect($redirect->to, $redirect->sort);
        }

        return $this->pageNotFound();
    }
}
