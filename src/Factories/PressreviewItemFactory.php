<?php

namespace FCNPressespiegel\Factories;

use FCNPressespiegel\Models\PressreviewItem;

class PressreviewItemFactory
{
    public static function create(
        string $title,
        string $url,
        string $excerpt,
        \DateTime $created
    ): PressreviewItem {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host']; #
        $pressreviewItem = new PressreviewItem();
        $pressreviewItem->setOriginalTitle($title);
        $pressreviewItem->setUrl($url);
        $pressreviewItem->setHost($host);
        $pressreviewItem->setDisplayTitle(
            $title . ' | ' . string($host)->remove('www.'),
        );
        $pressreviewItem->setExcerpt($excerpt);
        $pressreviewItem->setCreated($created);
        return $pressreviewItem;
    }
}
