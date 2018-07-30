<?php

class install extends \Robo\Tasks {

    function run() {
        $remote = 'https://resgef@bitbucket.org/resgef/temp.git';
        $this->exec("git clone $remote $server_target_dir")
                ->exec(//permissions
                        call_user_func(function() use($server_target_dir) {
                            $commands = [
                                "chmod -R 755 $server_target_dir"
                            ];
                            foreach ($this->builder->permission as $p) {
                                $mode = reset($p);
                                $path = "$server_target_dir/" . next($p);
                                $commands[] = "chmod -R $mode $path";
                            }
                            return implode(' && ', $commands);
                        })
                )->exec(//import mysql
                call_user_func(function() use($server_target_dir, $install_sql, $conf) {
                    return "mysql -u {$conf->dbmysqli->username} -p {$conf->dbmysqli->password} {$conf->dbmysqli->database} < $server_target_dir/" . basename($install_sql);
                }));
    }

}
