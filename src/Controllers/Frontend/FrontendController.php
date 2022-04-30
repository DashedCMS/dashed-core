<?php

namespace Qubiqx\QcommerceCore\Controllers\Frontend;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Artesaos\SEOTools\Facades\SEOTools;
use Qubiqx\QcommerceCore\Classes\Locales;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceEcommerceCore\Models\Order;
use Qubiqx\QcommerceEcommerceCore\Events\Orders\OrderIsPushableForReviewEvent;

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

        $routeModels = cms()->builder('routeModels');

        seo()->metaData('twitterSite', Customsetting::get('default_meta_data_twitter_site'));
        seo()->metaData('twitterCreator', Customsetting::get('default_meta_data_twitter_site'));

        foreach ($routeModels as $routeModel) {
            $response = $routeModel['routeHandler']::handle([
                'slug' => $slug,
            ]);

            if (is_a($response, \Illuminate\View\View::class)) {
                return $response->render();
            } elseif ($response == 'pageNotFound') {
                return $this->$response();
            }
        }

        return $this->pageNotFound();
    }
}
