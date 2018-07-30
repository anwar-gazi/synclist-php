<?php

class EbayXmlHelper
{

    /**
     *
     * @param SimpleXMLElement $element
     * @return bool
     */
    static function is_simplexml($element)
    {
        if (is_object($element) && ($element instanceof SimpleXMLElement)) {
            return true;
        } else {
            return false;
        }
    }

    static function has_childnode($element)
    {
        if (empty($element) || !($element instanceof SimpleXMLElement)) {
            return false;
        }
        if (empty($element->children())) {
            return false;
        } else { // has children
            /** remember, atributes are returned as children too, thats misleading */
            $vars = get_object_vars($element);
            if ((count($vars) == 1) && array_key_exists('@attributes', $vars)) {
                return false;
            }
            return true;
        }
    }

    /**
     * get the value of child node by key or chain of keys(key is the nodename)
     * you can get a childnode by nodename like SimpleXmlElement_node->Foo, and nested childs by
     * SimpleXmlElement_node->Foo->Bar->Tar, so why this method?
     * because you can get the value of simplified nodename chain like Foo.Bar.Tar
     * which is helpful as DBTable classes has their fields ebay source defined in annotation like that way
     *
     * @param SimpleXMLElement $node
     * @param string $prop
     * @return mixed Object or string Object[SimpleXmlElement] when the result has still child node
     * string when bottom child node reached, so returned its value, or if not found then empty
     */
    static function val(SimpleXMLElement $node, $prop)
    {
        $nodename = $node->getName();

        /** in case user supplied the root node name in the begining of $prop */
        $prop = str_replace("$nodename.", '', $prop);
        /** if child wanted, providing nested field names with dot seperated */
        $keys = explode('.', $prop);
        foreach ($keys as $key) {
            if (empty($node)) {
                break;
            }
            $node = $node->{$key};
        }
        if (self::has_childnode($node)) {
            return $node;
        } else {
            return (string)$node;
        }
    }

    static function simplexml_to_array_assoc(SimpleXMLElement $element)
    {
        return json_decode(json_encode($element), true);
    }

    static function simplexml_to_stdclass(SimpleXMLElement $element)
    {
        return json_decode(json_encode($element));
    }

    /**
     * check if the node is an order node, by checking nodename
     */
    static function is_order_node(simplexmlelement $node)
    {
        $nodename = (string)$node->getName();
        if ($nodename == 'Order') {
            return true;
        } else {
            return false;
        }
    }

    static function fetch_api($xml)
    {
        $Xml = self::xmlstr_to_xmldom($xml);
        return $Xml->getName();
    }

    /**
     * convert sml string into xml dom
     */
    static function xmlstr_to_xmldom($xml)
    {
        $responseDoc = new DomDocument();
        $responseDoc->loadXML($xml);
        $response = simplexml_import_dom($responseDoc);
        return $response;
    }

    static function is_valid_xml($xml)
    {
        trigger_error("dont use this function yet", E_USER_ERROR);
        $xml = XMLReader::open('test.xml');
        $xml->setParserProperty(XMLReader::VALIDATE, true);
        return $xml->isValid();
    }

    /*
     * check for any error in xml, either empty string, 404 or ebay error node
     * @param string $xml
     */

    static function check_error($xml)
    {
        $error = '';
        if (self::is_404($xml)) {
            $error = "Error: not xml string, its 404";
            return $error;
        }
        if ($error = self::check_error_node($xml)) {
            return $error;
        }
    }

    /*
     * if the (supposed) xml string is empty or http 404
     */

    static function is_404($xml)
    {
        if (stristr($xml, 'HTTP 404') || !$xml) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * check for error node in ebay return xml, if errors found the n raise user level warning
     * @return boolean equavalent, the error message if errors found, false if not found
     */
    static function check_error_node($xml)
    {
        $error = '';
        if (!$xml) {
            $error = 'empty response';
        } else {
            $responseDOM = self::xmlstr_to_xmldom($xml);
            $error = self::check_error_node_xmldom($responseDOM);
        }
        return $error;
    }

    /**
     * @param simplexmlelement $XmlDOM
     * @return bool|string
     */
    static function check_error_node_xmldom(simplexmlelement $XmlDOM)
    {
        $node_transpose = '';
        $node_transpose = function (simplexmlelement $node) use (&$node_transpose) {
            $arr = [];
            /** @var SimpleXMLElement $n */
            foreach ($node as $n) {
                if (!empty($n->children())) {
                    $a = $node_transpose($n);
                    $arr = array_merge($arr, $a);
                } else {
                    $arr[] = "{$n->getName()}:$n";
                }
            }
            return $arr;
        };

        $error = [];

        if ($XmlDOM->getName() == 'eBay') { //possibly request api name error
            foreach ($XmlDOM->Errors->Error as $Err) { //each error node
                $error = $node_transpose($Err);
            }
        } else {
            if (!empty($XmlDOM->Errors)) {
                $error = $node_transpose($XmlDOM->Errors);
            }
        }

        if (empty($error)) {
            return false;
        } else {
            return implode(',', $error);
        }
    }

    /*
     * save the xml in filesystem, dont abide save_xml setting, if want it to abide then write a wrapper
     * we dont abide that because that setting is for api objects,
     * so we already implemented a wrapper method in the EbaAPI class which extends this class
     * that method abides the setting
     *
     * @param string $xml, the $xml string to save
     * @param string $file, the path or filename of the file inside which to save xml
     * you may or may not provide a .xml extension
     * you keep it empty them we use php's late static binding t get the caller class name take current timestamp as filename
     *
     * @return the full path to the file created
     *
     * @important: here we analyze the $file before saving:
     * whatever path info you provide with the filename,
     * we take that with respect to our default xml save directory
     * as example: if you provide file.xml, then we assume <our default xml dir>/file.xml
     * if you provide sample/file.xml then we assume <our default xml dir>/sample/file.xml ... and so on
     * you may or may not provide the .xml extension
     * the path you provided(`sample` for sample/file.php), if that doesnt exist already then
     * we create the directory, add another directory named by current time, then and save the file
     * if that exist already, then we add another directory named by current time and save the file
     * in any case, if a matching filename found(example: basename.xml), then we  overrite//*change the filename by basename_1_<current timestamp>.xml //
     *
     */

    static function save_xml_fs($xml, $file)
    {
        if (!$xml || !$file) {
            trigger_error('cannot save xml: xml content or filename empty');
            return false;
        }
        $default_root = ROOT_DIR . 'data/pulled_xml/';

        $today = Carbon\Carbon::now()->format("Y.m.d-H");

        $file = trim($file, '.'); // we dont take things like . or ./
        if (!$file)
            $file = time();
        $File = new SplFileInfo($file);
        $extension = $File->getExtension();
        $basename = $File->getBasename($extension);
        $filename = $File->getFilename();
        $dir = $File->getPath();

        if (!$extension)
            $extension = '.xml';

        if (!$dir || ($dir == '.')) { // user sent only the filename
            $savedir = $default_root . $today . '/';
        } else {
            $savedir = $default_root . $dir . '/' . $today . '/';
        }

        if (!is_dir($savedir)) {
            mkdir($savedir, 0777, true); // create recursively nested if needed
        }

        $fullpath = $savedir . $basename . $extension;

        if (is_file($fullpath)) { // file exist already
            $fullpath = $savedir . $basename . '_1_' . Carbon\Carbon::now()->format('Y.m.d-H-i') . $extension;
        }

        file_put_contents($fullpath, $xml);

        return $fullpath;
    }

    /*
     * @name stringm, we prefer it to be an ebay api name or substring of api name
     * @return array of matched xml file absolute path(check DirHelper::search_filename_recursive doc)
     */

    static function get_saved_xml_files($name)
    {
        $default_root = ROOT_DIR . 'data/pulled_xml/';
        $matched_files = DirHelper::search_filename_recursive($default_root, $name, 'xml');
        return $matched_files;
    }

    static function search_xml_fs($name)
    {
        return self::get_saved_xml_files($name);
    }

    /*
     * getonly one file content
     * @return string absolute filepath
     */

    static function get_saved_xml_file($filename)
    {
        $xmls = self::get_saved_xml_files($filename);
        return @$xmls[0];
    }

    /*
     * search for a member element by name inside a xml node
     * @param string $needle
     * @param simplexmlelement or xml string $node, the haystack

     * @return true/false, null if error
     *
      static function search_key($needle, $node) {
      if (!is_string($needle)) {
      trigger_error("search error: needle must be string, ".gettype($needle)." provided!");
      return null;
      }
      if (is_string($node)) {
      $node = self::xmlstr_to_xmldom($node);
      } elseif (is_object($node) && (get_class($node)==simplexmlelement)) {
      //
      } else {
      trigger_error("search error: haystack must be simplexmlelement object, ".gettype($node)." provided!");
      return null;
      }

      if (!empty($node->xpath("$needle"))) {
      return true;
      } else {
      return false;
      }
      } */
}
