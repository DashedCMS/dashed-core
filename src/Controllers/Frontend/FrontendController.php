<?php

namespace Qubiqx\QcommerceCore\Controllers\Frontend;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Qubiqx\QcommerceCore\Classes\Locales;
use Qubiqx\QcommerceCore\Models\Customsetting;

class FrontendController extends Controller
{
    public function pageNotFound()
    {
        seo()->metaData('metaTitle', 'Pagina niet gevonden');

        if (View::exists('qcommerce.not-found.show')) {
            return response()->view('qcommerce.not-found.show')->setStatusCode(404);
        } else {
            abort(404);
        }
    }

    public function index($slug = null)
    {
        foreach (Locales::getLocales() as $locale) {
            if (Str::startsWith($slug, $locale['id'] . '/') || $slug == $locale['id']) {
                $slug = Str::substr($slug, strlen($locale['id']) + 1);
            }
        }

        seo()->metaData('twitterSite', Customsetting::get('default_meta_data_twitter_site'));
        seo()->metaData('twitterCreator', Customsetting::get('default_meta_data_twitter_site'));

        foreach (cms()->builder('routeModels') as $routeModel) {
            $response = $routeModel['routeHandler']::handle([
                'slug' => $slug,
            ]);

            if (is_a($response, \Illuminate\View\View::class)) {
                $schemas = seo()->metaData('schemas');
                $schemas['localBusiness']->name(seo()->metaData('metaTitle'));
                if (seo()->metaData('metaImage')) {
                    $schemas['localBusiness']->image(app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', seo()->metaData('metaImage'), []));
                }
                seo()->metaData('schemas', $schemas);
                seo()->metaData('metaImage', app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', seo()->metaData('metaImage'), []));

                return $response->render();
            } elseif ($response == 'pageNotFound') {
                return $this->$response();
            }
        }

        return $this->pageNotFound();
    }
}
