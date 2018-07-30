<?php

namespace resgef\synclist\system\datatypes\version;

class Version
{
    public $version;
    public $timestamp;

    function __construct($version, $timestamp)
    {
        $this->version = $version;
        $this->timestamp = $timestamp;
    }
}