<?php
/**
 * more about getting git commit log in specific format
 * https://stackoverflow.com/questions/25563455/how-do-i-get-last-commit-date-from-git-repository
 * https://git-scm.com/docs/pretty-formats
 * https://stackoverflow.com/questions/7853332/how-to-change-git-log-date-formats
 *
 */

namespace resgef\synclist\system\helper\versioning;

use Carbon\Carbon;
use resgef\synclist\system\datatypes\version\Version;

class Versioning
{
    static function get_git_last_commit_timestamp($repo_path)
    {
        $output = shell_exec("cd $repo_path && git init && git log -1 --format=%ct");
        preg_match('#([0-9]+)#', $output, $m);
        $last_commit_timestamp = $m[1];
        return $last_commit_timestamp;
    }

    static function get_version_from_timestamp($timestamp)
    {
        return Carbon::createFromTimestampUTC($timestamp)->format("Y n.j.G");
    }

    static function write_version_file($filepath, $version, $commit_timestamp)
    {
        file_put_contents($filepath, "version: $version\ntimestamp: $commit_timestamp");
    }

    /**
     * @param $filepath
     * @param array $variable_names
     * @return Version
     */
    static function read_version_file($filepath, array $variable_names = null)
    {
        $contents = file_get_contents($filepath);
        preg_match("#version:(.+)#", $contents, $v);
        preg_match("#timestamp:(.+)#", $contents, $t);

        $v = new Version(trim($v[1]), trim($t[1]));
        return $v;
    }
}