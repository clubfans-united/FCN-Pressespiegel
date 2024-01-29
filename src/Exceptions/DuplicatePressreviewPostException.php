<?php

namespace FCNPressespiegel\Exceptions;

use Exception;
use Throwable;

class DuplicatePressreviewPostException extends Exception
{
    public function __construct(
        $message = '',
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(
            __(
                'Dieser Artikel existiert schon im Pressespiegel',
                'fcn-pressespiegel'
            ),
            $code,
            $previous,
        );
    }
}
