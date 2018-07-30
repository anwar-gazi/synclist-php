<?php

Interface EbayXmlRequestInterface {
    function request($api_name, Array $xml_builder_options);
}