<?php

namespace Qubiqx\QcommerceCore\Classes;

use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Qubiqx\QcommerceCore\Models\Page;

class PageRouteHandler
{
    public static function handle($paramenters = [])
    {
        $slug = $paramenters['slug'] ?? '';
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
                return 'pageNotFound';
            }
        }
    }
}
