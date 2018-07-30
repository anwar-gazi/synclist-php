<?php

class ModelTemplateDelete extends Model {
    function delete($id) {
        $query = $this->db->query("DELETE FROM ".TEMPLATE_TABLE." WHERE id=$id");
        return $this->db->countAffected();
    }
}