<?php

function list_commands($classname) {
    if (!class_exists($classname)) {
        print("class `$classname` not yet loaded\n");
        return;
    }
    $reflection = new ReflectionClass($classname);

    $pub_methods = array_map(function($method) use ($classname) {
        if ($method->class != $classname) {
            return false;
        }
        if (in_array($method->name, ['factory', '__call', '__construct'])) {
            return false;
        }

        return $method->name;
    }, $reflection->getMethods(ReflectionMethod::IS_PUBLIC));

    $commands = array_filter($pub_methods);
    return $commands;
}

function is_empty_dir($abspath) {
    $I = new FilesystemIterator($abspath);
    if (iterator_count($I)) {
        return false;
    } else {
        return true;
    }
}

function is_abspath($abspath) {
    if (strpos($abspath, '/') !== 0) {
        return false;
    } else {
        return true;
    }
}

function is_git_dir($dir) {
    if (file_exists("$dir/.git")) {
        return true;
    } else {
        return false;
    }
}

function git_need_commit($dir) {
    $output = '';
    exec("git --git-dir=$dir/.git --work-tree=$dir status", $output);
    if (strpos(implode(" ", $output), 'Changes not staged for commit') !== FALSE) {
        return true;
    } else {
        return false;
    }
}

function git_list_branches($path) {
    $heads_path = $path . '/.git/refs/heads';
    $branches = [];
    foreach (new DirectoryIterator($heads_path) as $fileInfo) {
        if ($fileInfo->isFile()) {
            $branches[] = $fileInfo->getFilename();
        }
    }
    return $branches;
}

function git_current_branch($path) {
    $HEAD_path = $path . '/.git/HEAD';
    return end(explode(file_get_contents($HEAD_path), '/'));
}

function git_remote_remove_all($path) {
    $remotes = [];
    exec("git --git-dir=$path/.git --work-tree=$path remote", $remotes);
    foreach ($remotes as $remote) {
        exec("git --git-dir=$path/.git --work-tree=$path remote remove $remote");
    }
}

function search_table_name($needle, $database, $user, $pass) {
    $output = [];
    exec("mysql -u $user -p$pass $database -e 'show tables'", $output);
    foreach ($output as $table) {
        if (strpos($table, $needle)!==FALSE) {
            return $table;
        }
    }
}

function array_to_qstr(Array $arr) {
    $qstr = [];
    foreach ($arr as $key => $val) {
        if (!$val) {
            continue;
        }
        $qstr[] = "$key='$val'";
    }
    return implode(',', $qstr);
}
