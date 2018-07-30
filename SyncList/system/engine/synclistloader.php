<?php

final class SyncListLoader
{

    private $kernel;

    public function __construct(SyncListKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function module_module($rel_path, $context)
    {
        if (!is_object($context) || !($context instanceof SyncListModule)) {
            print("module_module load fail, context invalid\n");
            return;
        }
        $path = $context->config->path->root . "/$rel_path.php";
        if (!is_file($path)) {
            print("module_module load fail, not a file: $path\n");
            return false;
        } else {
            require_once $path;
            return true;
        }
    }

    /**
     * it takes variable number of arguments after @param $module_name ,
     * like: [Loader]->module($name, $foo, $bar, ...);
     *
     * this is where the life of a module starts
     * this is a very clever function, let me describe
     * each module's parent class constructor requires three things:
     * synclist kernel, module config, module root path
     * but if the module's constructor requires additional then ???
     * well dude, no more array for passing variable number of arguments
     * just pass the additional parameters to this function(after $module_name), then mogic!
     * example: if your module requires DBMySQLi module, then do:
     * $db = $Kernel->load->module('DBMySQLi');
     * $Kernel->load->module('your_module_name', $db)
     * under the hood: this creates your module as:
     * new $classname($kernel, $module_config, $module_root, $db)
     *
     * @param string $module_name the module directory name
     * @param closure $builder_function if you want to build the module yourself,
     * this is required when we need to alter some parts of the module config
     * before instantiating the module!
     * @return object your module object
     */
    public function module($module_name, $builder_function = null)
    {
        $module_root_path = $this->kernel->config->path->module . strtolower($module_name) . DIRECTORY_SEPARATOR;
        if (!is_dir($module_root_path)) {
            trigger_error("module '$module_name' doesnt exist");
            return null;
        }

//        $manifest_path = $module_root_path . "manifest.yml";
//        $module_conf = YamlWrapper::compile($manifest_path, true);
        $module_conf = json_decode(json_encode(require $module_root_path . "/manifest.php"));

        $module_index_file = $module_root_path . $module_conf->index_file;
        $module_index_class = $module_conf->index_class;
        if (!is_file($module_index_file)) {
            trigger_error("module index file $module_index_file doesnt exist!", E_USER_WARNING);
            return null;
        }
        require_once $module_index_file;
        if (!class_exists($module_index_class)) {
            trigger_error("module index class $module_index_class doesnt exist", E_USER_WARNING);
            return null;
        }

        if ($builder_function && is_callable($builder_function)) {
            return $builder_function($module_index_class, $module_conf, $module_root_path);
        } else {
            $args = [$this->kernel, $module_conf, $module_root_path];
            for ($i = 1; $i < func_num_args(); $i++) {
                $args[] = func_get_arg($i);
            }
            $reflect = new ReflectionClass($module_index_class);
            return $reflect->newInstanceArgs($args);
            //return new $module_index_class($this->kernel, $module_conf, $module_root_path);
        }
    }

    public function library($name)
    {
        $library_basepath = $this->kernel->config->path->library;
        $library_file = $library_basepath . "$name.php";
        require_once "$library_file";
    }

    public function system($system_filepath)
    {
        $root_path = $this->kernel->config->path->root;
        $system_filepath = ltrim($system_filepath, '/');

        require_once "$root_path/system/$system_filepath";
    }

}
