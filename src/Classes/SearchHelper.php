<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedPages\Models\Page;

class SearchHelper
{
    public static function getSearchPageUrl(): string
    {
        $pageId = Customsetting::get('search_page_id');
        $page = Page::publicShowable()->where('id', $pageId)->first();

        return $page->getUrl() ?? '#';
    }
}
