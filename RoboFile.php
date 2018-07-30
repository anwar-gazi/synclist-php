<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = __DIR__;

/** our tasks are here */
$tasks_dir = __DIR__ . '/robotasks';
$sltasks_dir = __DIR__ . '/robotasks/synclisttasks';

$GLOBALS['tasks_dir'] = $tasks_dir;

require_once "$sltasks_dir/reflectionfacility.php";
require_once "$sltasks_dir/exec.php";

require_once __DIR__ . '/vendor/autoload.php';
require_once "$sltasks_dir/helper.php";
require_once "$sltasks_dir/synclistbuildlib.php";
require_once "$sltasks_dir/phinxhelper.php";

require_once __DIR__ . "/system/library/db/mysqli.php";
require_once __DIR__ . '/SyncList/system/helper/yamlwrapper.php';

class RoboFile extends \Robo\Tasks
{
    use \Robo\Common\TaskIO;
    use \Exec;

    public function push($opts = ['server' => '', 'build-config' => '/home/droid/creds/synclist/build.yml'])
    {
        $builder = new SynclistBuildLib();

        $server = $opts['server'];
        $build_config_file = $opts['build-config'];
        if (!$server || !$build_config_file) {
            $this->say("Error: target server name empty or build config filepath empty");
            return 1;
        }
        $conf = YamlWrapper::compile($build_config_file)[$server];
        if (!$conf) {
            print("Error: config for $server not found\n");
            return 1;
        }
        if (git_need_commit($conf['install']['local_repo'])) {
            print("Error: Changes not staged for commit\n");
            $msg = $this->ask("commit message: ");
            $this->exec("git add . && git commit -m '$msg'");
        }
        $conf = json_decode(json_encode(array_merge($conf['install'], $conf['update'])));

        $src_repo_path = $conf->local_repo;
        $branch = $conf->local_repo_branch;
        $remote_repo = $conf->relay_repo;
        $pack_dir = "{$conf->build_dir}/upload/";
        $install_dir = "$pack_dir/install/";
        $install_sql = "$install_dir/install.sql";
        $mysql_root_pass = $conf->mysql_root_pass;

        if (!file_exists("$pack_dir/.git")) { //not a git repo so clean it
            $this->exec("rm -rf $pack_dir");
        }
        if (!file_exists($pack_dir)) { //init git and remote
            $this->exec("mkdir -p $pack_dir && mkdir -p $install_dir && cd $pack_dir && git init && git remote add origin $remote_repo && git config user.name 'Minhajul Anwar' && git config user.email 'polarglow06@gmail.com'");
        }

        $this->exec("cd $pack_dir && git init && git remote remove origin && git remote add origin $remote_repo && git pull $src_repo_path $branch");

        if ($conf->send_sql) {
            if (file_exists($install_sql)) {
                $this->printTaskInfo("skipping demo db sql build. $install_sql exists");
            } else {
                $builder->build_demo_db($conf->src_db, $install_sql, $mysql_root_pass, $conf);
            }
        }

        # compile the config
        $this->exec(function () use ($pack_dir, $conf, $src_repo_path) {
            $compiled_conf = YamlWrapper::compile("$src_repo_path/config/config.yml");
            YamlWrapper::edit("$pack_dir/config/config.yml", function () use ($conf, $compiled_conf) {
                $compiled_conf['http_url'] = $conf->url;
                $compiled_conf['https_url'] = $conf->url;
                $compiled_conf['site_name'] = $conf->site_name;
                $compiled_conf['cronstate'] = $conf->cronstate;
                $compiled_conf['error_reporting'] = $conf->error_reporting;
                $compiled_conf['dbmysqli'] = \json_decode(\json_encode($conf->dbmysqli), true);
                return $compiled_conf;
            });
            return YamlWrapper::edit($pack_dir . '/phinx.yml', function ($data) use ($compiled_conf, $pack_dir) {
                $db_creds = YamlWrapper::compile("$pack_dir/config/config.yml")['dbmysqli'];
                $data['environments']['default_database'] = 'defaultdb';
                $data['environments']['defaultdb'] = [
                    'adapter' => 'mysql',
                    'host' => $db_creds['hostname'],
                    'port' => '',
                    'charset' => 'utf8mb4',
                    'prefix' => $db_creds['table_prefix'],
                    'user' => $db_creds['username'],
                    'pass' => $db_creds['password'],
                    'name' => $db_creds['database'],
                ];
                return $data;
            });
        });

        $this->exec(implode(' && ', array_map(function ($p) use ($pack_dir) {
            $mode = reset($p);
            $path = "$pack_dir/" . next($p);
            return "chmod -R $mode $path";
        }, SynclistBuildLib::$permissions)));

        if (git_need_commit($pack_dir)) {
            $this->exec("cd $pack_dir && git init && git add . && git commit -m 'built'");
        }

        $this->exec("cd $pack_dir && git push origin master --force");

        $this->printTaskSuccess("the distribution built and pushed to relay repo: $remote_repo");
        return 0;
    }

    /**
     * @return int
     */
    public function adduser()
    {
        $creds = YamlWrapper::compile(__DIR__ . "/config/config.yml")['dbmysqli'];
        $db = new \DB\MySQLi($creds['hostname'], $creds['username'], $creds['password'], $creds['database']);
        $table = search_table_name('portal_users', $creds['database'], $creds['username'], $creds['password']);

        $login_user = $this->ask("login username: ");
        if (!$login_user) {
            print("Error: empty\n");
            return false;
        }
        $u = strtolower($login_user);
        if ($db->query("select * from $table where username='$u'")->num_rows) {
            if ($this->confirm("username exist, remove it?")) {
                $db->query("delete from $table where username='$u'");
                $this->say("removed");
                if (!$this->confirm("now add $u as new user?")) {
                    return true;
                }
            } else {
                $this->printTaskInfo("remember the password");
                return true;
            }
        }

        $login_pass = $this->ask("$login_user password: ");
        if (!$login_pass) {
            print("Error: empty\n");
            return false;
        }

        $validity = $this->ask("validity?(eg. 10m/h/d for 10 minutes/hours/days, empty for permanent login)");
        $duration = 0;
        if ($validity) {
            $matches = [];
            if (preg_match('#(\d+)([mhd]{1})#', $validity, $matches)) {
                $duration = $matches[1] * call_user_func(function () use ($matches) {
                        $dur = 0;
                        switch ($matches[2]) {
                            case 'm':
                                print("{$matches[1]} minutes\n");
                                $dur = 60;
                                break;
                            case 'h':
                                print("{$matches[1]} hours\n");
                                $dur = 60 * 60;
                                break;
                            case 'd':
                                print("{$matches[1]} days\n");
                                $dur = 24 * 60 * 60;
                                break;
                        }
                        return $dur;
                    });
            } else {
                print("Error: invalid duration\n");
                return 0;
            }
        }

        $salt = time();
        $p = sha1($salt . sha1($salt . sha1($login_pass)));

        $db->query("insert into $table set username='$u',password='$p',salt='$salt',validity_period_sec='$duration',created='" . Carbon::now()->toISO8601String() . "'");
        $this->say("success");
        return 1;
    }

    /**
     * xtra: set db creds. force migrate: robo.phar phinx migrate -f <migration_file_name_part>
     * phinx is a database migrations manager helper in php
     * limitations: only phinx commands, phinx switches are not available
     */
    public function phinx(Array $commands, $custom = ['force|f' => false, 'all' => false])
    {
        /* phinx installed */
        if (!is_file(realpath('') . '/vendor/bin/phinx') || !class_exists('Phinx\Config\Config')) {
            print("phinx not installed or not included\n");
            return false;
        }
        require_once __DIR__ . '/SyncList/init.php';
        /* now set the db creds in phinx config */
        $db_creds = new_synclist_kernel()->load->module('app.api', false)->db_creds();
        YamlWrapper::edit(__DIR__ . '/phinx.yml', function ($data) use ($db_creds) {
            $data['environments']['default_database'] = 'defaultdb';
            $data['environments']['defaultdb'] = [
                'adapter' => 'mysql',
                'host' => $db_creds->hostname,
                'port' => '',
                'charset' => 'utf8mb4',
                'prefix' => $db_creds->table_prefix,
                'user' => $db_creds->username,
                'pass' => $db_creds->password,
                'name' => $db_creds->database,
            ];
            return $data;
        });
        $ph = PhinxHelper::factory(__DIR__, __DIR__ . '/db/migrations/');
        $command = "php -f vendor/robmorgan/phinx/bin/phinx ";
        if (!empty($commands) && ($commands[0] == 'migrate')) {
            $versions = array_slice($commands, 1);
            if ($custom['force']) {
                if ($custom['all'] && empty($versions)) {
                    $ph->phinx_clear_cache_all();
                } else {
                    $ph->phinx_clear_cache($versions);
                }
            }
            $commands = ['migrate'];
        }
        foreach ($commands as $ins) {
            $command .= " $ins";
        }
        system($command);
    }

    public function perms()
    { # supposed to run in server
        $to = __DIR__;
        $perm_entries = array_map(function ($p) use ($to) {
            $mode = reset($p);
            $path = "$to/" . next($p);
            return "chmod -R $mode $path";
        }, SynclistBuildLib::$permissions);
        $this->exec(implode(' && ', $perm_entries));
    }

    public function income_stats() {
        /** @var SyncListApi $api */
        $api = require_once __DIR__.'/SyncList/synclist.php';
        $api->init_db();
        $stats = $api->SellingStats->monthly_selling();
        print_r($stats);
    }
}