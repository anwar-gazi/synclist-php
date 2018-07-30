<?php

/**
 * @property DBMySQLi $db
 * @property PDO $pdo
 * @property SyncListApi $synclist_api synclist api
 * @property Loader $load
 * @property Config $config
 * @property Log $log
 * @property Request $request
 * @property Response $response
 * @property Cache $cache Description
 * @property Session $session Description
 * @property Language $language
 * @property Document $document Description
 * @property Url $url
 */
abstract class Controller
{

    /** @var Registry $registry */
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function __get($key)
    {
        $val = $this->registry->get($key);
        return $val;
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

}
