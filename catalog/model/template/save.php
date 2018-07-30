<?php

class ModelTemplateSave extends Model {

    function create_draft($template_data) {
        $timenow = DTHelpers\now();
        $name = Carbon\Carbon::now()->format('YmdHis');
        $this->db->query("INSERT INTO " . TEMPLATE_TABLE . " SET html='" . $this->db->escape($template_data) . "',name='" . $this->db->escape($name) . "',savetime='" . $timenow . "',template_type_draft=1");
        return $this->db->getLastId();
    }

    function create_template($template_data, $template_name) {
        $timenow = DTHelpers\now();
        $this->db->query("INSERT INTO " . TEMPLATE_TABLE . " SET html='" . $this->db->escape($template_data) . "',name='" . $this->db->escape($template_name) . "',savetime='" . $timenow . "',template_type_draft=0");
        return $this->db->getLastId();
    }

    function update_draft($template_data, $template_id) {
        $timenow = DTHelpers\now();
        $res = $this->db->query("UPDATE " . TEMPLATE_TABLE . " SET html='" . $this->db->escape($template_data) . "',savetime='" . $timenow . "',template_type_draft=1 WHERE id=".(int) $template_id);
        return $this->db->countAffected();
    }

    function update_template($template_data, $template_id) {
        $timenow = DTHelpers\now();
        $res = $this->db->query("UPDATE " . TEMPLATE_TABLE . " SET html='" . $this->db->escape($template_data) . "',savetime='" . $this->db->escape($timenow) . "',template_type_draft=0 WHERE id=" . $this->db->escape($template_id));
        return $this->db->countAffected();
    }

    function template_exist($tpl_name) {
        return $this->db->query("SELECT id FROM " . TEMPLATE_TABLE . " WHERE name='$tpl_name'")->num_rows;
    }

    function draft_to_template($template_id, $template_name) {
        $timenow = DTHelpers\now();
        $res = $this->db->query("UPDATE " . TEMPLATE_TABLE . " SET savetime='" . $timenow . "',template_type_draft=0,name='" . $this->db->escape($template_name) . "' WHERE id=" . $this->db->escape($template_id));
        return $this->db->countAffected();
    }

}
