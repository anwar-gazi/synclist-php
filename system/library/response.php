<?php

class Response
{
    /** @var string[] $headers */
    private $headers = [];

    // some important headers, these will always override whatever raw headers you set in $headers
    private $content_type = 'text/html';
    private $charset = 'utf-8';
    private $status_code = '200';
    private $status_code_msg = '';
    private $level = 0;

    private $output;

    private $more = [];

    public function set($key, $val)
    {
        $this->more[$key] = $val;
    }

    public function get($key)
    {
        return $this->more[$key];
    }

    public function addHeader($header)
    {
        $this->headers[] = $header;
    }

    public function redirect($url, $status = 302)
    {
        $target = str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url);
        header('Location: ' . $target, true, $status);
        exit();
    }

    public function setCompression($level)
    {
        $this->level = $level;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function setStatusCode($http_status_code, $message)
    {
        $this->status_code = $http_status_code;
        $this->status_code_msg = $message;
    }

    public function setContentType($content_type, $charset = 'utf-8')
    {
        $this->content_type = $content_type;
        $this->charset = $charset;
    }

    public function getOutput()
    {
        return $this->output;
    }

    private function compress($data, $level = 0)
    {
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)) {
            $encoding = 'gzip';
        }

        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)) {
            $encoding = 'x-gzip';
        }

        if (!isset($encoding) || ($level < -1 || $level > 9)) {
            return $data;
        }

        if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
            return $data;
        }

        if (headers_sent()) {
            return $data;
        }

        if (connection_status()) {
            return $data;
        }

        $this->addHeader('Content-Encoding: ' . $encoding);

        return gzencode($data, (int)$level);
    }

    public function output()
    {
        if ($this->output) {
            if ($this->level) {
                $output = $this->compress($this->output, $this->level);
            } else {
                $output = $this->output;
            }

            if (!headers_sent()) {
                foreach ($this->headers as $header) {
                    header($header, true);
                }

                header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' ' . $this->status_code . ' ' . $this->status_code_msg);

                header("Content-Type: {$this->content_type}; charset={$this->charset}", true);
            }

            echo $output;
        }
    }
}