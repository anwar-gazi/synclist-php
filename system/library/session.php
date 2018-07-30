<?php

class Session
{
    public $data = array();

    public function __construct()
    {
        if (!session_id()) {
            ini_set('session.use_only_cookies', 'On');
            ini_set('session.use_trans_sid', 'Off');
            ini_set('session.cookie_httponly', 'On');

            session_set_cookie_params(0, '/');
            session_start();
        }

        $this->data =& $_SESSION;
    }

    public function getId()
    {
        return session_id();
    }

    public function destroy()
    {
        return session_destroy();
    }

    /**
     * setter/getter
     * set or get a flashdata
     * @param mixed $flash whatever you want to store
     * @return mixed
     */
    public function flash($flash = null)
    {
        if ($flash) { //setter
            $this->data['flashdata'] = $flash;
        } else { //getter
            $flash = array_key_exists('flashdata', $this->data) ? $this->data['flashdata'] : '';
            unset($this->data['flashdata']);
            return $flash;
        }
    }

    function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    function get($name, $default_value)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        } else {
            return $default_value;
        }
    }

    function get_and_unset($name, $default_value)
    {
        if (array_key_exists($name, $this->data)) {
            $val = $this->data[$name];
            unset($this->data[$name]);
            return $val;
        } else {
            return $default_value;
        }
    }

    function __get($name)
    {
        return $this->data[$name];
    }
}