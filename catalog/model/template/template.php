<?php

class ModelTemplateTemplate extends Model {
    function list_templates() {
        return $this->db->query('SELECT * FROM '.TEMPLATE_TABLE)->rows;
    }
    
    function get_template($id) {
        return $this->db->query("SELECT * FROM ".TEMPLATE_TABLE." WHERE id=$id")->row;
    }
    
    function list_template_names() {
        return $this->db->query('SELECT id,name,template_type_draft FROM '.TEMPLATE_TABLE." WHERE template_type_draft=0")->rows;
    }
    
    function get_html($template_id) {
        return $this->db->query("SELECT id,html FROM ".TEMPLATE_TABLE." WHERE id=$template_id")->row['html'];
    }
}