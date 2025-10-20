<?php

namespace FCNPressespiegel\Models;

class Source
{
    private string $url;

    private $filter;

    public function __construct(string $url, ?callable $filter = null)
    {
        $this->url = $url;
        $this->filter = $filter;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getFilter(): ?callable
    {
        return $this->filter;
    }
}
