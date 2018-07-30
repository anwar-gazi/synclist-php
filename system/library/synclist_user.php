<?php

/**
 * || by resgef
 * the portal users definition. This users are those who logs into the listing portal
 * this is veryh light and designed for hanksminerals listing
 */
class SyncList_user
{
    private $db_prefix;

    public function __construct($registry)
    {
        $this->config = $registry->get('config');
        $this->db = $registry->get('db');
        $this->request = $registry->get('request');
        $this->session = $registry->get('session');
        $this->db_prefix = $this->config->get('dbmysqli')['default']['table_prefix'];
    }

    static function saltify($password, $salt)
    {
        return sha1($salt . sha1($salt . sha1($password)));
    }

    public function login($username, $password)
    {
        $customer_query = $this->db->query("SELECT * FROM " . $this->db_prefix . "portal_users WHERE LOWER(username) = '" . $this->db->escape(utf8_strtolower($username)) . "' AND password = '" . $this->db->escape(trim($password)) . "'");

        if ($customer_query->num_rows) {
            $this->session->data['username'] = $customer_query->row['username'];
            return true;
        } else {
            return false;
        }
    }

    public function login2($username, $password)
    {
        $table = $this->db_prefix . 'portal_users';
        $u = strtolower($username);
        $row = $this->db->query("select * from $table where username='$u'")->row;
        if (!empty($row)) {
            $validity = $row['validity_period_sec'];
            $created = Carbon::createFromFormat(DateTime::ISO8601, $row['created']);
            /** validity not indefinite and already passed */
            if ($validity && $created->addSeconds($validity)->lt(Carbon::now())) {
                $this->session->flash("login validity period expired!");
                return false;
            }
            $salt = $row['salt'];
            $p = $this->saltify($password, $salt);
            if ($p === $row['password']) {
                $this->session->data['username'] = $u;
                return true;
            } else {
                $this->session->flash("password wrong!");
                return false;
            }
        } else {
            $this->session->flash("username not found!");
            return false;
        }
    }

    public function new_login_info($username, $password)
    {
        $salt = time();
        $u = strtolower($username);
        $p = $this->saltify($password, $salt);
        $this->db->query("insert into " . $this->db_prefix . "portal_users set username='$u',password='$p',salt='$salt'");
        return $this->db->countAffected();
    }

    public function logout()
    {

        unset($this->session->data['username']);

        //$this->username = '';
    }

    public function isLogged()
    {
        return @$this->session->data['username'];
    }

    public function getUsername()
    {
        return $this->session->data['username'];
    }

}
