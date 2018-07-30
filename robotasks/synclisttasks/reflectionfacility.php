<?php

/**
 * Class ReflectionFacility
 * a trait you can plug with you class to make some methods treated as cli command
 * what this trait does:
 * ridiculously easy to use, being a trait you can just plug with a class
 * simple method to command translate syntax, just prepend 'command__' with your class method
 * invoke easy, from your class object just call __invoke($command, $arguments)
 * simple cli help generation, analyze available commands andto show a list of comands,
 * analyze a command method arguments to show the command help
 */
trait ReflectionFacility
{
    
    function __is_command($method)
    {
        if (strpos($method, 'command__') === 0) {
            return true;
        } else {
            return false;
        }
    }
    
    private function __man()
    {
        $ret = [
            'methods' => []
        ];
        $method_opts = function (ReflectionMethod $reflectionMethod) {
            $ret = [
                'required_params' => [],
                'optional_params' => []
            ];
            foreach ($reflectionMethod->getParameters() as $Param) {
                $paramname = $Param->getName();
                if ($Param->isDefaultValueAvailable()) {
                    $default_val = $Param->getDefaultValue();
                    $default_val = $default_val === '' ? "''" : $default_val;
                    $paramname = $paramname . '[' . $default_val . ']';
                }
                if ($Param->isOptional()) {
                    $ret['optional_params'][] = $paramname;
                } else {
                    $ret['required_params'][] = $paramname;
                }
            }
            return $ret;
        };
        
        $Ref = new ReflectionClass(get_class($this));
        
        foreach ($Ref->getMethods() as $reflectionMethod) {
            if (!$this->__is_command($reflectionMethod->getName())) {
                continue;
            }
            $ret['methods'][$reflectionMethod->getName()] = $method_opts($reflectionMethod);
        }
        
        return $ret;
    }
    
    function __command_exists($command)
    {
        $method = "command__$command";
        if (method_exists($this, $method)) {
            return $method;
        } else {
            return false;
        }
    }
    
    function __method_to_command($method)
    {
        return str_replace('command__', '', $method);
    }
    
    function __command_to_method($command)
    {
        return "command__$command";
    }
    
    private function __doc()
    {
        $methods_doc = [];
        foreach ($this->__man()['methods'] as $method_name => $method_doc) {
            $command = $this->__method_to_command($method_name);
            $required = array_map(function ($name) {
                return "--$name";
            }, $method_doc['required_params']);
            $optional = array_map(function ($name) {
                return "[--$name]";
            }, $method_doc['optional_params']);
            
            $required = implode(' ', $required);
            $optional = empty($optional) ? '' : implode(' ', $optional);
            
            $methods_doc[$command] = sprintf("$command: %s %s", $required, $optional);
        }
        return $methods_doc;
    }
    
    function __doc_string_command($command)
    {
        return $this->__doc()[$command];
    }
    
    function __doc_string_commands()
    {
        return implode("\n", $this->__doc());
    }
    
    /**
     * in simplest cases, use this method
     * @param $command
     * @param array $options
     * @return int
     */
    function __invoke($command, Array $options)
    {
        if (!$this->__command_exists($command)) {
            $this->printTaskError("not a command");
            $this->printTaskInfo("available: \n" . $this->__doc_string_commands() . "\n");
            return 1;
        }
        
        $method = $this->__command_to_method($command);
        
        $required_options = [];
        foreach ($this->__man()['methods'][$method]['required_params'] as $paramname) {
            $required_options[$paramname] = '';
        }
        $provided_options = $options;
        
        $missing = [];
        foreach ($required_options as $optname => $nothing) {
            $optval = $provided_options[$optname];
            if ($optval === null) {
                $missing[] = $optname;
            }
        }
        
        if (!empty($missing)) {
            $missing_opts = array_map(function ($optname) {
                return "--$optname";
            }, $missing);
            $this->printTaskError("not enough arguments, missing " . implode(',', $missing_opts));
            $this->printTaskInfo($this->__doc_string_command($command));
            return 1;
        }
        
        # now prepare to invoke
        $params = [];
        
        # prepare the slots
        $refMethod = new ReflectionMethod(get_class($this), $method);
        for ($i = 0; $i < $refMethod->getNumberOfParameters(); $i++) {
            $params[$i] = '';
        }
        
        foreach ($refMethod->getParameters() as $reflectionParameter) {
            $name = $reflectionParameter->getName();
            $pos = $reflectionParameter->getPosition();
            $val = $options[$name];
            $params[$pos] = $val;
        }
        
        return call_user_func_array([$this, $method], $params);
        
    }
}