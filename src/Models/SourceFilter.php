<?php

namespace FCNPressespiegel\Models;

class SourceFilter
{
    private string $field;

    private string $contains;

    private $filter = null;

    public function __construct(string $field, string $contains, ?callable $filter = null)
    {
        $this->field = $field;
        $this->contains = $contains;
        $this->filter = $filter;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getContains(): string
    {
        return $this->contains;
    }

    public function getCallback(): ?callable
    {
        return $this->filter;
    }
}
