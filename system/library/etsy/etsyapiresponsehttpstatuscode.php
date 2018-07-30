<?php

namespace resgef\synclist\system\library\etsy\etsyapiresponsehttpstatuscode;

class EtsyApiResponseHttpStatusCode
{
    private $http_status_code;

    /**
     * remember etsy could send other codes like 401
     * parse them according their range
     *
     * general rule: any codes except 200 and 201 means error(for etsy),
     * codes in 400 domain means a problem from client side(rest api spec),
     * codes in 500 range means a problem in server(rest api spec)
     *
     * (for etsy) codes in 400 domain(except 400,403,404) means a problem in your api keys/invalid keys
     *
     * @var array
     */
    static $meaning = [
        '200' => 'Success!',
        '201' => 'A new resource was successfully created.',
        '400' => 'You\'ve made an error in your request (such as passing a string for a parameter that expects a number).',
        '403' => 'You\'ve exceeded the rate limits for your account, or the data you\'re trying to access is private.',
        '404' => 'The requested resource could not be found, or the URI doesn\'t correspond to any known command.',
        '500' => 'An internal error on our side. If this problem persists, submit a bug report in the bug section of our forums.',
        '503' => 'The Etsy API is down for scheduled maintenance; please try again later (this should be rare!)',
    ];

    function __construct($http_status_code)
    {
        $this->http_status_code = $http_status_code;
    }

    function isOk()
    {
        return $this->http_status_code == 200;
    }

    function isCreated()
    {
        return $this->http_status_code == 201;
    }

    function isBadRequest()
    {
        return $this->http_status_code == 400;
    }

    function isForbidden()
    {
        return $this->http_status_code == 403;
    }

    function isNotFound()
    {
        return $this->http_status_code == 404;
    }

    /**
     * not(yet) specified in etsy api docs, but we get this from our tests
     * @return bool
     */
    function isUnauthorized()
    {
        return $this->http_status_code == 401;
    }

    function isServerError()
    {
        return $this->http_status_code == 500;
    }

    function isServiceUnavailable()
    {
        return $this->http_status_code == 503;
    }

    /**
     * @return string
     */
    function getMeaning()
    {
        return self::$meaning[$this->http_status_code];
    }
}