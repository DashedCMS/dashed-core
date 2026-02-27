<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedArticles\Models\Article;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\Review;

class Reviews
{
    public static function get($limit = 12, $orderBy = 'created_at', $order = 'DESC', int $minStars = 0, bool $random = false)
    {
        $reviews = Review::where('stars', '>=', $minStars);

        if ($random) {
            $reviews->inRandomOrder();
        }

        return $reviews->limit($limit)->orderBy($orderBy, $order)->get();
    }

    public static function getOverviewUrl(): ?string
    {
        $pageId = Customsetting::get('review_overview_page_id');
        if($pageId){
            $page = Review::find($pageId);
            if($page){
                return $page->getUrl() ?? '#';
            }
        }
        return '#';
    }
}
