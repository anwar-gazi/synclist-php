<?php

/**
 * normalized xml node
 */
class XmlNode {

    private $node;

    function __construct(SimpleXMLElement $node) {
        $this->node = $node;
    }

    /**
     * 
     * get any properties of the xml node
     * for any nosted properties, you can get the value in one call
     * by building one pseudo property name by adding all property names from top to the target
     * with a . (dot)
     * if the query reaches bottom, the string value of bottom node returned,
     * or the resulted node is returned
     * example: for nested child C in  <A><B><C></C></B></A>
     * you can get either by $node->A->B->C or $node->'A.B.C'
     * 
     * @param string $key
     * @return mixed, string/simplexmlelement, string if bottom reached
     * 
     */
    function __get($key) {
        $parts = explode('.', $key);
        $node = $this->node;
        foreach ($parts as $p) {
            $node = $node->$p;
        }
        if (empty($node->children())) { // we are in the bottom, so get the string
            return (string) $node;
        } else { // we are not in the bottom, return as it is
            return $node;
        }
    }

    /**
     * 
     * transpose into a plain array or multidimensional array
     * or you can extract some specific fields by supplying their names
     * 
     * @param array $fieldnames if you want to extract some specific field values
     * 
     * @param bool $set_keys when you provide $fieldnames as associative array,
     * what do you want the returned array keys to be?
     * the keys of the $fieldnames? (true)
     * or the values of the $fieldnames? (false)
     * 
     * @return array values are extracted from xml, keys are the keys/elements of $fieldnames
     * 
     */
    function __toArray(Array $fieldnames = [], $set_keys = false) {
        $info = [];
        if (!empty($fieldnames)) {
            foreach ($fieldnames as $keyname => $fieldname) {
                if ($set_keys) {
                    $key = $keyname;
                } else {
                    $key = $fieldname;
                }
                $info[$key] = $this->{$fieldname};
            }
        }
        return $info;
    }

}
