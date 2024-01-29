<?php

namespace FCNPressespiegel\Models;

class PressreviewSourceFilter
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $contains;

    /**
     * @param string $field
     * @param string $contains
     */
    public function __construct(string $field, string $contains)
    {
        $this->field = $field;
        $this->contains = $contains;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getContains(): string
    {
        return $this->contains;
    }
}
