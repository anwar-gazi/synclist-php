<?php

namespace resgef\synclist\system\datatypes\remoteprovider;

use resgef\synclist\system\exceptions\remoteproviderisnotsupported\RemoteProviderIsNotSupportedException;

class RemoteProvider
{
    /**
     * ebay/etsy/amazon...
     * @var string $name
     */
    public $name;

    function __construct($provider_name)
    {
        if ($this->provider_is_supported($provider_name)) {
            $this->name = $provider_name;
        } else {
            throw new RemoteProviderIsNotSupportedException("provider $provider_name is not supported");
        }
    }

    /**
     *
     * @param string $provider_name
     * @return bool
     */
    private function provider_is_supported($provider_name)
    {
        $supported_providers = [
            'ebay', 'etsy'
        ];
        if (in_array($provider_name, $supported_providers)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return RemoteProvider
     */
    static function ebay()
    {
        return new self('ebay');
    }

    /**
     * @return RemoteProvider
     */
    static function etsy()
    {
        return new self('etsy');
    }

    function __toString()
    {
        return $this->name;
    }
}