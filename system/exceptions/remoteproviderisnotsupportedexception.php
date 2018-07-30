<?php

namespace resgef\synclist\system\exceptions\remoteproviderisnotsupported;

use Throwable;

class RemoteProviderIsNotSupportedException extends \Exception
{
    function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}