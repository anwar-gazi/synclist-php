<?php

function join_path()
{
    $parts = [];
    for ($i = 0; $i < func_num_args(); $i++) {
        $p = func_get_arg($i);
        if ($i==0) {
            $p = rtrim($p, '/');
        } elseif ($i==func_num_args()) {
            $p = ltrim($p, '/');
        } else {
            $p = trim($p, '/');
        }
        $parts[] = $p;
    }
    return implode('/', $parts);
}