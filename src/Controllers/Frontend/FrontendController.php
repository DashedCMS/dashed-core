<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\NotFoundOccurence;
use Dashed\DashedCore\Models\NotFoundPage;
use Dashed\DashedEcommerceCore\Jobs\UpdateProductInformationJob;
use Dashed\DashedEcommerceCore\Models\Product;
use Dashed\DashedPages\Models\Page;
use Dashed\Seo\Jobs\ScanSpecificResult;
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
        NotFoundPage::saveOccurrence(str(request()->fullUrl())->replace(request()->root(), ''), 404, request()->header('referer'), request()->userAgent(), request()->ip(), Sites::getActive(), app()->getLocale());

        seo()->metaData('metaTitle', 'Pagina niet gevonden');

        if (View::exists(Customsetting::get('site_theme', null, 'dashed') . '.not-found.show')) {
            return response()->view(Customsetting::get('site_theme', null, 'dashed') . '.not-found.show')->setStatusCode(404);
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
//                    $schemas['localBusiness']->image(app(UrlBuilder::class)->url('dashed', seo()->metaData('metaImage'), [
//                        'widen' => 1200,
//                    ]));
//                    seo()->metaData('metaImage', app(UrlBuilder::class)->url('dashed', seo()->metaData('metaImage'), [
//                        'widen' => 1200,
//                    ]));
                    $schemas['localBusiness']->image(mediaHelper()->getSingleMedia(seo()->metaData('metaImage'), 'huge')->url ?? '');
                    seo()->metaData('metaImage', mediaHelper()->getSingleMedia(seo()->metaData('metaImage'), 'huge')->url ?? '');
                }
                seo()->metaData('schemas', $schemas);

                return $response->render();
            } elseif ($response == 'pageNotFound') {

                if ($redirect = Redirect::where('from', $slug)->orWhere('from', '/' . $slug)->orWhere('from', $slug . '/')->orWhere('from', '/' . $slug . '/')->first()) {
                    return redirect($redirect->to, $redirect->sort);
                }

                return $this->$response();
            }
        }

        if ($redirect = Redirect::where('from', $slug)->orWhere('from', '/' . $slug)->orWhere('from', $slug . '/')->orWhere('from', '/' . $slug . '/')->first()) {
            return redirect($redirect->to, $redirect->sort);
        }

        return $this->pageNotFound();
    }
}
