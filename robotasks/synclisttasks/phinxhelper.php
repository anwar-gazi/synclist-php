<?php

class PhinxHelper
{
    
    public $db;
    public $root;
    public $migrations_dir;
    
    /**
     *
     * @param string $root the system root where vendor and migrations folder are
     */
    static function factory($root, $migrations_dir)
    {
        $me = new self();
        if (!$root) {
            print("error! provide root directory\n");
            return false;
        }
        if (!$migrations_dir) {
            print("error! provide migrations directory\n");
            return false;
        }
        $me->root = $root;
        $me->migrations_dir = $migrations_dir;
        //require "$root_dir/vendor/robmorgan/phinx/app/phinx.php";
        $config = Phinx\Config\Config::fromYaml("{$root}/phinx.yml");
        $Env = $config->getEnvironment($config->getDefaultEnvironment());
        $me->db = new Phinx\Db\Adapter\MysqlAdapter($Env);
        return $me;
    }
    
    public function phinx_clear_cache_all()
    {
        $sql = "DELETE FROM phinxlog";
        $prompt = trim(readline("\n*****clear all history???*****\n"));
        if (!$prompt) {
            $affected = $this->db->execute($sql);
            if ($affected) {
                print("success! all history cleared\n");
            }
        } else {
            print("abort\n");
        }
    }
    
    public function phinx_clear_cache(Array $versions)
    {
        if (empty($versions)) {
            return true;
        }
        
        /** @var array $info search filesystem for migration filename contain $part */
        $existing_mig = $this->phinx_fetch_migration_versions();
        $matched_versions = [];
        foreach ($versions as $v) {
            foreach ($existing_mig as $basename => $migration_info) {
                if (strpos($basename, $v) !== FALSE) { // portion found in filename
                    $matched_versions[] = $migration_info;
                }
            }
        }
        if (empty($matched_versions)) {
            print("no migration file for '$v'\n");
        }
        
        foreach ($matched_versions as $migration_info) {
            $p = trim(readline("remove history for version {$migration_info->version} file {$migration_info->file}??"));
            if ($p) {
                print("dont remove\n");
                continue;
            }
            $sql = "DELETE FROM phinxlog WHERE version='{$migration_info->version}'";
            $affected = $this->db->execute($sql);
            if ($affected) {
                print("success! {$migration_info->version} history removed\n");
            } else {
                print("history doesnt exist or fail\n");
            }
        }
        return false;
    }
    
    /**
     * fetch version numbers from migrations in filesystem
     * @return Array of \stdClass
     */
    private function phinx_fetch_migration_versions()
    {
        $rd = $this->migrations_dir;
        $files = scandir($rd);
        $all = [];
        foreach ($files as $path) {
            $base = basename($path, '.php');
            $matches = [];
            if (!is_file($rd . '/' . $path)) {
                continue;
            }
            if (preg_match('#^zzz\.#', $base)) {
                continue;
            }
            if (preg_match('#^([\d]+)_#', $base, $matches) !== 1) {
                continue;
            }
            $info = new stdClass();
            $info->version = $matches[1];
            $info->file = $path;
            
            $all[$base] = $info;
        }
        
        return $all;
    }
    
}
