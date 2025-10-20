<?php

namespace FCNPressespiegel\Factories;

use DateTime;
use FCNPressespiegel\Models\PressreviewItem;

class PressreviewItemFactory
{
    public static function create(string $title, string $url, string $excerpt, DateTime $created): PressreviewItem
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];
        $pressreviewItem = new PressreviewItem();
        $pressreviewItem->setOriginalTitle($title);
        $pressreviewItem->setUrl($url);
        $pressreviewItem->setHost($host);
        $pressreviewItem->setDisplayTitle(
            $title . ' | ' . str_replace('www.', '', $host),
        );
        $pressreviewItem->setExcerpt($excerpt);
        $pressreviewItem->setCreated($created);
        return $pressreviewItem;
    }
}
