<?php

namespace resgef\synclist\system\exceptions\unmetdependency;

use Throwable;

class UnmetDependency extends \Exception
{
    function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}