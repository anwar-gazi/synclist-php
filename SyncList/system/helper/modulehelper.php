<?php

class ModuleHelper {

    /**
     * build tables descriptions(schema and source map) from module manifest config->dbmysqli
     * @param stdClass $config
     * @return Array
     * [
     *  'table_name_without_prefix'=>
     *  [
     *      'schema'=>'',
     *      'fieldmap'=>
     *      [
     *          dbfield=>sourcemap
     *      ]
     *  ]
     * ]
     */
    static function desc_table(stdClass $config) {
        $tables = [];
        foreach ($config->dbmysqli->tables as $table_name => $fields) {
            if (!is_object($fields)) {
                continue;
            }
            $schema_start = "create table `{$config->dbmysqli->table_prefix}{$table_name}` ( ";
            $map = [];
            $schema_parts = [];
            foreach ($fields as $field_name => $desc) {
                $schema_parts[] = "\n`$field_name` {$desc->def}";
                if (property_exists($desc, 'source')) { // sourcemap specified
                    $map[$field_name] = $desc->source;
                }
            }
            $schema = $schema_start . implode(',', $schema_parts) . " ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $tables[$table_name] = [
                'schema' => $schema,
                'fieldmap' => $map
            ];
        }
        return $tables;
    }

    /**
     * generate the schema of a schefic table
     * @param type $table_name_without_prefix
     * @param stdClass $config
     * @return type
     */
    static function schema($table_name_without_prefix, stdClass $config) {
        $desc = self::desc_table($config);
        return $desc[$table_name_without_prefix]['schema'];
    }

    /**
     * generate the schema of a schefic table
     * @param type $table_name_without_prefix
     * @param stdClass $config
     * @return type
     */
    static function fieldmap($table_name_without_prefix, stdClass $config) {
        $desc = self::desc_table($config);
        return $desc[$table_name_without_prefix]['fieldmap'];
    }

    /**
     * xml node to info array
     * @param SimpleXMLElement $node
     * @param array $map
     * @return type
     */
    static function node_to_array(SimpleXMLElement $node, Array $map) {
        $set = [];
        $leading = $node->getName().'.';
        foreach ($map as $fieldname => $path) {
            if (strpos($path, $leading)!==FALSE) {
                $path = str_replace($leading, '', $path);
            }
            $set[$fieldname] = self::get_node_val($node, $path);
        }
        return $set;
    }

    /**
     *
     */
    static function get_node_val(SimpleXMLElement $node, $path) {
        $parts = explode('.', $path);
        $elem = $node;
        foreach ($parts as $nodename) {
            $elem = $elem->{$nodename};
        }
        if (EbayXmlHelper::has_childnode($elem)) {
            return $elem;
        } else {
            return (string) $elem;
        }
    }

}
