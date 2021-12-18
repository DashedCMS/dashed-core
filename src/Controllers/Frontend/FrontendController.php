<?php

namespace Qubiqx\QcommerceCore\Controllers\Frontend;

use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Artesaos\SEOTools\Facades\SEOTools;
use Qubiqx\QcommerceCore\Models\Page;

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
        if ($slug) {
            $page = Page::publicShowable()->where('slug->' . App::getLocale(), $slug)->where('is_home', 0)->first();
        } else {
            $page = Page::publicShowable()->where('is_home', 1)->first();
        }

        if ($page) {
            if (View::exists('qcommerce.pages.show')) {
                SEOTools::setTitle($page->meta_title ?: $page->name);
                SEOTools::setDescription($page->meta_description);
                SEOTools::opengraph()->setUrl(url()->current());
                $metaImage = $page->getFirstMediaUrl('meta-image-' . App::getLocale());
                if ($metaImage) {
                    SEOTools::addImages($metaImage);
                }

                View::share('page', $page);

                return view('qcommerce.pages.show');
            } else {
                return $this->pageNotFound();
            }
        }

        return $this->pageNotFound();
    }
}
