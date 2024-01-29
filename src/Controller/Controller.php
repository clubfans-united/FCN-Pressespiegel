<?php

namespace FCNPressespiegel\Controller;

trait Controller
{
    public static function &init(): ?static
    {
        static $instance = null;
        $class = static::class;

        if ($instance instanceof $class === false) {
            $instance = new $class();
        }
        return $instance;
    }
}
