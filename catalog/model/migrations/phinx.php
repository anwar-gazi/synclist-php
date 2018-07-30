<?php

class Modelmigrationsphinx extends Model
{
    function remove_phinxlog($version)
    {
        $this->db->query("delete from phinxlog where version=$version");
        return $this->db->countAffected();
    }
}