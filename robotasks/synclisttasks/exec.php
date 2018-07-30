<?php

trait Exec {

    /**
     * 
     * @param type mixed $cmd function/closure or command string(eg "ls -la")
     * function/closure: we call it, if the return value is a \Robo\Result then we evaluate success/fail
     * if the return is truthy/falsy, we evaluate success/fail
     * if success then we forward, if fail then we halt
     * @return die()
     */
    function exec($cmd) {
        if (is_callable($cmd)) {
            $result = $cmd();
            if ($result instanceof \Robo\Result) {
                if ($result->wasSuccessful()) {
                    return $this;
                } else {
                    $this->printTaskError("fail");
                    die();
                }
            } else {
                if ($result) {
                    return $this;
                } else {
                    $this->printTaskError("fail");
                    die();
                }
            }
        } elseif (is_string($cmd)) {
            $return_var = '';
            passthru($cmd, $return_var);
        } else {
            die("unrecognized argument to exec");
        }
        if ($return_var === 0) {
            return true;
        } else {
            $this->printTaskError("fail");
            die();
        }
    }

}
