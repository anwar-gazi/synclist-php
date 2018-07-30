<?php

class DataObject {

    private $data = [];

    function __construct($data) {
        if (EbayXmlHelper::is_simplexml($data)) {
            $this->data = EbayXmlHelper::simplexml_to_array_assoc($data);
        } elseif (is_array($data)) {
            $this->data = $data;
        }
        return $this;
    }

    function __get($prop) {
        if (array_key_exists($prop, $this->data)) {
            $data = new self($this->data->$prop);
        } else {
            $data = new self();
        }
        return $data;
    }

}
