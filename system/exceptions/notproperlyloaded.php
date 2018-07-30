<?php

namespace resgef\synclist\system\exceptions\notproperlyloaded;

use Throwable;

class NotProperlyLoaded extends \Exception
{
    function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}