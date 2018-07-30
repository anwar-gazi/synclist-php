<?php

class EbayApiResponse extends SyncListModule {

    private $Xml;
    private $error;

    public function load($xml) {
        $this->Xml = '';
        $this->error = '';
        $error = \EbayXmlHelper::check_error($xml);
        if ($error) {
            $this->error = $error;
            return;
        }

        $this->Xml = \EbayXmlHelper::xmlstr_to_xmldom($xml);

        return $this;
    }

    function has_node($name) {

    }

    function __get($prop) {
        switch ($prop) {
            case 'total_page':
                $val = (string) $this->Xml->PaginationResult->TotalNumberOfPages;
                break;
            case 'total_page_activelist':
                $val = (string) $this->Xml->ActiveList->PaginationResult->TotalNumberOfPages;
                break;
            case 'api':
                $val = str_replace('Response', '', $this->Xml->getName());
                break;
            case 'error':
                $val = $this->error;
                break;
            default:
                $val = $this->Xml->$prop;
                break;
        }
        return $val;
    }

    function __call($method, $arguments) {
        return $this->Xml->$method();
    }

}
