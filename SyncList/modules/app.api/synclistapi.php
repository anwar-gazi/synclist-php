<?php

/**
 *
 * api to access synclist data, to integrate synclist at both backend and frontend
 *
 * at backend, you can call an api method to avoid raw:
 * loading the corresponding module, pulling data, then procesing data for intended results
 * use as:
 *
 * magic properties:
 * ****************
 * @property SyncListAPiOrders $Orders
 * @property SyncListAPiListing $Listing
 * @property SyncListApiLocalInventory $LocalInventory local inventory module
 * @property SyncListApiRemoteProvider $RemoteProvider
 * @property SyncListApiLogger $Logger
 * @property SyncListApiCronState $CronState
 * @property SyncListApiShippingLabel $ShippingLabel
 */
class SyncListApi extends SyncListModule
{
    /** @var  DBMySQLi */
    public $db;

    /** @var  SyncListApiLogger */
    public $log;

    function __construct(\SyncListKernel $kernel, \stdClass $config, $module_path, $init_db = true)
    {
        parent::__construct($kernel, $config, $module_path);

        $this->db = $this->load->module('dbmysqli');
        if ($init_db) {
            $this->init_db();
        }
        $this->log = $this->load->module('logger', $this->db, $this->table_name('synclist_logs'));
    }

    function init_db()
    {
        $creds = $this->db_creds();
        $this->db->connect($creds->hostname, $creds->username, $creds->password, $creds->database);
    }

    /**
     * load an api then use as this object property(magic)
     * @param string $api the api name, also to be accessed after loading as: $this->$api->...
     * @return boolean
     */
    public function load_api($api)
    {
        $api_file = __DIR__ . '/synclistapi_' . strtolower($api) . '.php';
        if (!is_file($api_file)) {
            trigger_error("cannot load api `$api`, file doesnt exist: $api_file");
            return null;
        }

        require_once $api_file;

        $api_class = "SyncListApi$api";
        if (!class_exists($api_class)) {
            trigger_error("cannot load '$api' api class '$api_class'");
            return null;
        } else {
            return new $api_class($this);
        }
    }

    function __get($api)
    {
        /** if api, this is last chance */
        return $this->load_api($api);
    }

    public function desc_table()
    {
        $tables = [];
        foreach ($this->config->dbmysqli->tables as $table_name => $fields) {
            if (!is_object($fields)) {
                continue;
            }
            $schema_start = "create table `{$this->table_name($table_name)}` ( ";
            $schema_parts = [];
            foreach ($fields as $field_name => $desc) {
                $schema_parts[] = "\n`$field_name` {$desc->def}";
            }
            $schema = $schema_start . implode(',', $schema_parts) . " ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $tables[$table_name] = [
                'schema' => $schema
            ];
        }
        return $tables;
    }

    public function schema($table_name_without_prefix)
    {
        $desc = $this->desc_table();
        return $desc[$table_name_without_prefix]['schema'];
    }

    public function fielddef($table_name_without_prefix, $fieldname)
    {
        return $this->config->dbmysqli->tables->{$table_name_without_prefix}->{$fieldname}->def;
    }

    /**
     * current default database connection credentials
     * @param $env
     * @return stdClass
     */
    function db_creds($env = 'default')
    {
        return $this->config->dbmysqli->{$env};
    }

    function database()
    {
        return $this->db_creds()->database;
    }

    /**
     * @param string $name_without_prefix
     * @return null|string
     */
    function table_name($name_without_prefix)
    {
        return "{$this->db_creds()->table_prefix}$name_without_prefix";
    }

    /**
     *
     * @param string $provider lob/ebay/etsy ...etc
     * @param string $user resgef/hanksminerals/eepstreats/...etc
     * @return stdClass
     */
    function api_keys($provider, $user)
    {
        return $this->config->api_keys->$provider->$user;
    }
}
