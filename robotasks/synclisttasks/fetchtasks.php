<?php

class FetchTasks extends \Robo\Tasks {

    public function hm_db() {
        $conf = YamlWrapper::toobject('/home/droid/creds/synclist/hanksminerals.yml');
        $file = "dump.{$conf->dbmysqli->database}.sql";
        $this
        ->taskSshExec($conf->ssh_host)
        ->stopOnFail(true)
        ->identityFile($conf->ssh_ifile)
        ->port($conf->ssh_port)
        ->user($conf->ssh_user)
        ->exec("mysqldump -u {$conf->dbmysqli->username} -p{$conf->dbmysqli->password} {$conf->dbmysqli->database} > $file")
        ->run();
        $this->_exec("scp -P {$conf->ssh_port} -i {$conf->ssh_ifile} {$conf->ssh_user}@{$conf->ssh_host}:$file $conf->local_save_dir/$file");
        $this->say("saved to {$conf->local_save_dir}/$file");
    }
    
    function __call($m, $a) {
        return list_commands(get_class());
    }

}
