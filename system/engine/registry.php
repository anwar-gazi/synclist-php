<?php

/**
 * Class Registry
 * @property Loader $load
 * @property Config $config
 * @property Url $url
 * @property Log $log
 * @property DBMySQLi $db
 * @property PDO $pdo
 * @property Request $request
 * @property Response $response
 * @property Cache $cache
 * @property Twig_Environment $twig
 */
final class Registry
{
    private $data = array();

    public function get($key)
    {
        return (isset($this->data[$key]) ? $this->data[$key] : null);
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function has($key)
    {
        return isset($this->data[$key]);
    }

    public function __get($key)
    {
        return (isset($this->data[$key]) ? $this->data[$key] : null);
    }
}