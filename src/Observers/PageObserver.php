<?php

namespace Qubiqx\QcommerceCore\Observers;

use Illuminate\Support\Str;
use Qubiqx\QcommerceCore\Models\Page;
use Qubiqx\QcommerceCore\Classes\Sites;

class PageObserver
{
    public function saving(Page $page)
    {
//        dd(request());
//        $page->site_id = request()->get('site_id', Sites::getFirstSite()['id']);
//        $page->setTranslation();

//        while (Page::where('slug->' . $translation['locale']['id'], $page->getTranslation('slug', $translation['locale']['id']))->count()) {
//            $page->setTranslation('slug', $translation['locale']['id'], $page->getTranslation('slug', $translation['locale']['id']) . Str::random(1));
//        }

//        $slug = $page->slug ?: $page->name;
//        $slug = str_replace('/', 'thisisaslash', $slug);
//        $slug = Str::slug($slug);
//        $slug = str_replace('thisisaslash', '/', $slug);
//        $page->slug = $slug;
    }
}
