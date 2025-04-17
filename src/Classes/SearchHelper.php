<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedPages\Models\Page;
use Dashed\DashedCore\Models\Customsetting;

class SearchHelper
{
    public static function getSearchPageUrl(): string
    {
        $pageId = Customsetting::get('search_page_id');
        $page = Page::publicShowable()->where('id', $pageId)->first();

        return $page->getUrl() ?? '#';
    }
}
