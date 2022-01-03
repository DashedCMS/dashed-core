<?php

namespace Qubiqx\QcommerceCore\Classes;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Qubiqx\QcommerceCore\Models\Page;
use Artesaos\SEOTools\Facades\SEOTools;

class PageRouteHandler
{
    public static function handle($parameters = [])
    {
        $slug = $parameters['slug'] ?? '';
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
                if ($page->meta_image) {
                    SEOTools::addImages($page->meta_image);
                }

                View::share('page', $page);

                return view('qcommerce.pages.show');
            } else {
                return 'pageNotFound';
            }
        }
    }
}
