<?php
/**
 * cron script $argv(parameters supplied with script) manager
 * all accepted values are defined here
 */

class ARGV {
    public $help = false;
    public $output = 'stdOut';
    function __construct(Array $argv) {
        if (@$argv[1]=='help') {
            $this->help = true;
        } else {
            $argv[0] = '';
            $argv = array_filter($argv);
            foreach($argv as $arg) {
                if (preg_match_all('#([a-z_]+)=([a-zA-Z,]+)#', $arg, $matches) !== FALSE) {
                    foreach($matches[1] as $index=>$varname) {
                        $this->$varname = $matches[2][$index];
                    }
                }
            }
        }
    }
    
    /**
     * show script output directly
     */
    function output_direct() {
        return !(strpos($this->output, 'stdOut')===FALSE);
    }
    
    function output_logfile() {
        return !(strpos($this->output, 'logFile')===FALSE);
    }
}