<?php

namespace FCNPressespiegel\Models;

class PressreviewSource
{
    private string $url;

    private ?PressreviewSourceFilter $filter;

    public function __construct(
        string $url,
        PressreviewSourceFilter $filter = null
    ) {
        $this->url = $url;
        $this->filter = $filter;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getFilter(): ?PressreviewSourceFilter
    {
        return $this->filter;
    }
}
