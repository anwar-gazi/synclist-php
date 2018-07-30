<?php

/**
 * @property SyncListKernel $synclist
 * @property SyncListApi $synclist_api synclist modules
 * @property Loader $load
 * @property Config $config
 * @property Log $log
 * @property Request $request
 * @property Response $response
 * @property Cache $cache Description
 * @property Session $session Description
 * @property Language $language
 * @property Document $document Description
 * @property DBMySQLi $db
 * @property PDO $pdo
 */
abstract class Model
{
    protected $registry;

    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }
}