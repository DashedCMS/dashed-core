<?php

namespace Dashed\DashedCore\Classes;

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
}
