<?php
// directory helpers

class DirHelper {
    /*
     * join paths, provide as many as you want as this method param
     * or provide them in array
     * but if you want to provide multiple param, then pass all as string
     * if you want to passs array, then pass only one array
     * if you dont folow this then your joined paths will contain multiple slash when 
     */
    static function join() {
        $portions = array();
        $f_args = func_get_args();
        $count_args = func_num_args();
        /*
         * array of paths passed
         */
        if (($count_args==1) && is_array($f_args[0])) { // array of paths passed
            $f_args = $f_args[0];
            $count_args = count($f_args);
        }
        for($i=0; $i<$count_args; $i++) {
            $p = $f_args[$i];
            if (is_array($p)) {
                $p = implode(DIRECTORY_SEPARATOR, $p);
            }
            if ($i===0) {
                $p = self::rtrim_slash($p);
            } else if ( ($i>0) && ($i<($count_args-1)) ) { // save for the last piece
                $p = self::trim_slash($p);
            } else { // last piece
                $p = self::ltrim_slash($p);
            }
            $portions[] = $p;
        }
        $fullpath = implode(DIRECTORY_SEPARATOR, $portions);
        return $fullpath;
    }
    /*
     * trim directory seperator slashes from the beginig and end of string
     */
    static function trim_slash($dir) {
        return trim($dir, DIRECTORY_SEPARATOR);
    }
    static function ltrim_slash($dir) {
        return ltrim($dir, DIRECTORY_SEPARATOR);
    }
    static function rtrim_slash($dir) {
        return rtrim($dir, DIRECTORY_SEPARATOR);
    }
    /*
     * make absolute paths from a portion of path with a context(base path)
     * ./ and ../ references must be at the begining to be resolved
     * also, if path is prefixed by / then nothing to resolve becase
     * / is not a reference, it means root directory
     * so /bal with context /foo/bar will remain /bal
     */
    static function realpath($path, $context) {
        $dot = '.';
        $ref_cur = $dot.DIRECTORY_SEPARATOR;
        /*
         * to match a ../ or chained things like ../../../  etc 
         */
        $double_dot = '..';
        $ref_one_up = $double_dot.DIRECTORY_SEPARATOR;
        
        $matched=false;
        $loop_count = 0;
        //TODO: its failing in some cases, check the tests
        while(1) {
            //print("path $path context $context \n");
            $loop_count++;
            
            //print("path $path context $context \n");
            if ($loop_count>5) {
                trigger_error("we dont allow going up more then six directories", E_USER_WARNING);
                $translated = basename($path).DIRECTORY_SEPARATOR.$context;
                return $translated;
            }
            
            if (!$context) {
                return $path;
            }
            if ( $context == dirname($context) ) { // topmost level reached, cannot be upped anymore(this happens for . and / )
                return (($context=='/')?$context:'').$path; // if context is / then add it b4
            }
            /*
            * dont just blindly look only for . but check ./ too 
            * to avoid matching things like .htaccess, .myfolder
            * if $path is only a . then match it too
            */
            if ( ($path==$dot) || !$path) {
                $path = $ref_cur;
            }
            if ( ($path==$double_dot)) {
                $path = $ref_one_up;
            }
            // now process for ./ or ../ prefix
            if ( strpos($path, $ref_cur)===0 ) { // path prefixed by ./
                $path = substr_replace($path, '', 0, 2); // omit the ./
                $mached = true;
                continue; // jump to next loop
            }
            if (strpos($path, $ref_one_up)===0) { // path prefixed by ../
                $context = dirname($context); // one level upped
                $path = substr_replace($path, '', 0, 3); // omit the double dot with slash
                $matched = true;
                continue; // jump to next loop
            }
            // didnt match anything on this run
            if ($matched) {
                $translated = (self::rtrim_slash($context)).DIRECTORY_SEPARATOR.(self::ltrim_slash($path));
                break;
            } else {
                if (strpos($path, DIRECTORY_SEPARATOR)===0) { // for paths like /bal/sal
                    return $path;
                }
                if (strpos($path, DIRECTORY_SEPARATOR)!==0) { // for paths like bal/sal
                    return (self::rtrim_slash($context)).DIRECTORY_SEPARATOR.$path;
                }
                $translated = $path;
                break;
            }
        }
        return $translated;
    }
    static function rootname($path) {
        //$first = array_filter(explode(DIRECTORY_SEPARATOR, $path))[0];
        if (strpos($path, DIRECTORY_SEPARATOR)===0) {
            $first = DIRECTORY_SEPARATOR.$first;
        }
        return $first;
    }
    static function list_files_recursive($directory, $extension) {
        $paths = array();
        $RootIterator = new recursivedirectoryiterator($directory);
        $allIterator = new recursiveiteratoriterator($RootIterator);
        foreach($allIterator as $I) {
            if ($I->isFile()) {
                if ($extension) {
                    if (strtolower($I->getExtension())==strtolower($extension)) { //extension mathced
                        $path = $I->getPathname();
                    } else { // extension not matched
                        continue;
                    }
                } else {
                    $path = $I->getPathname();
                }
                if (trim(file_get_contents($path))) { // file is not empty
                    $paths[] = $path;
                }
            }
        }
        return $paths;
    }
    static function search_filename_recursive($directory, $filename_contains, $extension) {
        $all_files_paths = self::list_files_recursive($directory, $extension);
        $search_result = array();
        foreach($all_files_paths as $path) {
            $basename = basename($path);
            if ($extension) {
                if (strtolower(pathinfo($path, PATHINFO_EXTENSION))!=strtolower($extension)) {
                    continue;
                }
                $basename = basename($path, $extension); // dont matched the extension string
            }
            if (strpos(strtolower($basename), strtolower($filename_contains))!==FALSE) {
                $search_result[] = $path;
            }
        }
        return $search_result;
    }
    static function list_xmlfiles($directory) {
        $xml_files = array();
        $DirIterator = new DirectoryIterator($directory);
        foreach($DirIterator as $iterator) {
            if ($iterator->isFile() && (strtolower($iterator->getExtension())=='xml')) {
                $xml_files[] = $iterator->getPathname();
            } elseif (!$iterator->isDot()) {
                trigger_error("not a xml file ".$iterator->getPathname(), E_USER_NOTICE);
            }
        }
        return $xml_files;
    }

    static function empty_directory($directory) {
        $files = array_diff(scandir($directory), array('..', '.'));
        foreach($files as $file) {
            $file = $directory.$file;
            if ( is_file($file) ) {
                if (!unlink($file)) {
                    trigger_error("cannot delete file ".$file, E_USER_WARNING);
                } else {
                    trigger_error("deleted file ".$file, E_USER_NOTICE);
                }
            } else {
                trigger_error("not a file ".$file, E_USER_NOTICE);
            }
        }
    }

    function dir_is_readable($directory) {
        $DirIterator = new DirectoryIterator($directory);
        return $DirIterator->isReadable();
    }
    function dir_is_writable($directory) {
        $DirIterator = new DirectoryIterator($directory);
        return $DirIterator->isWritable();
    }
    function dir_is_empty($directory) {
        $DirIterator = new DirectoryIterator($directory);
        foreach($DirIterator as $iterator) {
            if ($iterator->isFile()) {
                return false;
            }
        }
        return true;
    }
}