<?php

namespace Qubiqx\QcommerceCore\Controllers\Frontend;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Artesaos\SEOTools\Facades\SEOTools;
use Qubiqx\QcommerceCore\Classes\Locales;
use Qubiqx\QcommerceEcommerceCore\Models\Order;
use Qubiqx\QcommerceEcommerceCore\Events\Orders\OrderIsPushableForReviewEvent;

class FrontendController extends Controller
{
    public function pageNotFound()
    {
        SEOTools::setTitle('Pagina niet gevonden');
        SEOTools::opengraph()->setUrl(url()->current());

        if (View::exists('qcommerce.not-found.show')) {
            return view('qcommerce.not-found.show');
        } else {
            abort(404);
        }
    }

    public function index($slug = null)
    {
//        $order = Order::latest()->first();
//        $response = OrderIsPushableForReviewEvent::dispatch($order);
//        dump($response);
//        $order->refresh();
//        dd($order);
        foreach (Locales::getLocales() as $locale) {
            if (Str::startsWith($slug, $locale['id'] . '/') || $slug == $locale['id']) {
                $slug = Str::substr($slug, strlen($locale['id']) + 1);
            }
        }

        $routeModels = cms()->builder('routeModels');

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
