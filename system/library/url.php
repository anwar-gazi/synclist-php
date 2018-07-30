<?php

class Url
{

    private $domain;
    private $ssl;
    private $rewrite = array();
    private $config;

    public function __construct(Config $config)
    {
        $this->domain = $config->get('config_url');
        $this->ssl = $config->get('config_secure') ? $config->get('config_ssl') : $config->get('config_url');
        $this->config = $config;
    }

    public function addRewrite($rewrite)
    {
        $this->rewrite[] = $rewrite;
    }

    # https first
    public function root_url()
    {
        if ($this->config->get('ssl')) { # https enabled
            $url = 'https://' . $this->config->get('ssl_host');
        } else { # http
            $url = 'http://' . $this->config->get('host');
        }
        return rtrim($url, '/') . '/';
    }

    public function link($route = '', $args = '', $secure = true)
    {
        $url = $this->root_url();

        if (!$route) {
            return $url . 'index.php';
        }
        $url .= 'index.php?route=' . $route;

        if ($args) {
            $url .= str_replace('&', '&amp;', '&' . ltrim($args, '&'));
        }

        foreach ($this->rewrite as $rewrite) {
            $url = $rewrite->rewrite($url);
        }

        return $url;
    }

}
