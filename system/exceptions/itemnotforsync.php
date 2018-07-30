<?php

namespace resgef\synclist\system\exceptions\itemnotforsync;

use Throwable;

class ItemNotForSync extends \Exception
{
    function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}