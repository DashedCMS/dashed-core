<?php

namespace Qubiqx\QcommerceCore\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Artesaos\SEOTools\Facades\SEOTools;
use Qubiqx\QcommerceEcommerceCore\Events\Orders\OrderIsPushableForReviewEvent;
use Qubiqx\QcommerceEcommerceCore\Models\Order;

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
