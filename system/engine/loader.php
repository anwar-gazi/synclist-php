<?php

final class Loader
{

    private $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function controller($route, $args = array())
    {
        $action = new Action($route, $args);

        return $action->execute($this->registry);
    }

    public function _controller($controller, $cli = false)
    {
        if ($cli) {
            $file = DIR_CLI . 'controller/' . $controller . '.php';
        } else {
            $file = DIR_APPLICATION . 'controller/' . $controller . '.php';
        }
        $class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', $controller);

        if (file_exists($file)) {
            include_once($file);
            $obj = new $class($this->registry);
            return $obj;
        } else {
            trigger_error('Error: Could not load controller ' . $file . '!');
            exit();
        }
    }

    public function cli_controller($controller)
    {
        return $this->_controller($controller, true);
    }

    public function model($model)
    {
        $file = DIR_APPLICATION . 'model/' . $model . '.php';
        $class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $model);

        if (file_exists($file)) {
            include_once($file);

            $this->registry->set('model_' . str_replace('/', '_', $model), new $class($this->registry));
        } else {
            trigger_error('Error: Could not load model ' . $file . '!');
            exit();
        }
    }

    public function view($tpl_route, $context)
    {
        $tpl_basepath = DIR_TEMPLATE;
        /** @var Twig_Environment $twig */
        $twig = $this->registry->get('twig');
        return $twig->render($tpl_route, $context);
    }

    public function twig($tpl_route, $context) {
        return $this->view($tpl_route, $context);
    }

    public function library($library)
    {
        $file = DIR_SYSTEM . 'library/' . $library . '.php';

        if (file_exists($file)) {
            include_once($file);
        } else {
            trigger_error('Error: Could not load library ' . $file . '!');
            exit();
        }
    }

    public function helper($helper)
    {
        $file = DIR_SYSTEM . 'helper/' . $helper . '.php';

        if (file_exists($file)) {
            include_once($file);
        } else {
            trigger_error('Error: Could not load helper ' . $file . '!');
            exit();
        }
    }

    public function config($config)
    {
        $this->registry->get('config')->load($config);
    }

    public function language($language)
    {
        return $this->registry->get('language')->load($language);
    }

}
