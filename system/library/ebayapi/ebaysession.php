<?php

namespace Resgef\SyncList\System\Library\EbayApi\Ebaysession;

/*   2013 eBay Inc., All Rights Reserved */

/* Licensed under CDDL 1.0 -  http://opensource.org/licenses/cddl1.php */

/**
 * http://developer.ebay.com/devzone/xml/docs/Concepts/MakingACall.html
 * Class eBaySession
 * @package Resgef\SyncList\Lib\Ebaysession
 */
class eBaySession
{

    private $requestToken;
    private $devID;
    private $appID;
    private $certID;
    private $serverUrl;
    private $compatLevel;

    //SiteID must also be set in the Request's XML
    //SiteID = 0  (US) - UK = 3, Canada = 2, Australia = 15, .... get the list here: http://developer.ebay.com/devzone/XML/docs/Reference/eBay/types/SiteCodeType.html
    //SiteID Indicates the eBay site to associate the call with
    private $siteID;
    private $verb;

    /**    __construct
     * Constructor to make a new instance of eBaySession with the details needed to make a call
     * Input:
     * @param string $userRequestToken - the authentication token fir the user making the call
     * @param string $developerID - Developer key obtained when registered at http://developer.ebay.com
     * @param string $applicationID - Application key obtained when registered at http://developer.ebay.com
     * @param string $certificateID - Certificate key obtained when registered at http://developer.ebay.com
     * @param string $serverUrl
     * @param string $compatabilityLevel - API version this is compatable with
     * @param string $siteToUseID - the Id of the eBay site to associate the call iwht (0 = US, 2 = Canada, 3 = UK, ...)
     * @param string $callName - The name of the call being made (e.g. 'GeteBayOfficialTime')
     * Output:    Response string returned by the server
     */
    public function __construct($userRequestToken = '', $developerID = '', $applicationID = '', $certificateID = '', $serverUrl = '', $compatabilityLevel = '', $siteToUseID = '', $callName = '')
    {
        $this->requestToken = $userRequestToken;
        $this->devID = $developerID;
        $this->appID = $applicationID;
        $this->certID = $certificateID;
        $this->compatLevel = $compatabilityLevel;
        $this->siteID = $siteToUseID;
        $this->verb = $callName;
        $this->serverUrl = $serverUrl;
    }

    public function set_requestToken($userRequestToken)
    {
        $this->requestToken = $userRequestToken;
        return $this;
    }

    public function set_devID($developerID)
    {
        $this->devID = $developerID;
        return $this;
    }

    public function set_appID($applicationID)
    {
        $this->appID = $applicationID;
        return $this;
    }

    public function set_certID($certificateID)
    {
        $this->certID = $certificateID;
        return $this;
    }

    public function set_compatLevel($compatabilityLevel)
    {
        $this->compatLevel = $compatabilityLevel;
        return $this;
    }

    public function set_siteID($siteToUseID)
    {
        $this->siteID = $siteToUseID;
        return $this;
    }

    public function set_verb($callName)
    {
        $this->verb = $callName;
        return $this;
    }

    public function set_serverUrl($serverUrl)
    {
        $this->serverUrl = $serverUrl;
        return $this;
    }

    /**    sendHttpRequest
     * Sends a HTTP request to the server for this session
     * Input:    $requestBody
     * Output:    The HTTP Response as a String
     * @param $requestBody
     * @return array
     *
     */
    public function sendHttpRequest($requestBody)
    {
        //build eBay headers using variables passed via constructor
        $headers = $this->buildEbayHeaders();

        //initialise a CURL session
        $connection = curl_init();
        //set the server we are using (could be Sandbox or Production server)
        curl_setopt($connection, CURLOPT_URL, $this->serverUrl);

        //stop CURL from verifying the peer's certificate
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);

        //set the headers using the array of headers
        curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);

        //set method as POST
        curl_setopt($connection, CURLOPT_POST, 1);

        //set the XML body of the request
        curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);

        //set it to return the transfer as a string from curl_exec
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);

        //Send the Request
        $response = curl_exec($connection);

        $resp_info = curl_getinfo($connection);

        //close the connection
        curl_close($connection);

        return [
            'response' => $response,
            'transfer_info' => $resp_info
        ];
    }

    /**    buildEbayHeaders
     * Generates an array of string to be used as the headers for the HTTP request to eBay
     * Output:    String Array of Headers applicable for this call
     */
    private function buildEbayHeaders()
    {
        $headers = array(
            //Regulates versioning of the XML interface for the API
            'X-EBAY-API-COMPATIBILITY-LEVEL: ' . $this->compatLevel,
            //set the keys
            'X-EBAY-API-DEV-NAME: ' . $this->devID,
            'X-EBAY-API-APP-NAME: ' . $this->appID,
            'X-EBAY-API-CERT-NAME: ' . $this->certID,
            //the name of the call we are requesting
            'X-EBAY-API-CALL-NAME: ' . $this->verb,
            //SiteID must also be set in the Request's XML
            'X-EBAY-API-SITEID: ' . $this->siteID,
        );

        return $headers;
    }

}

?>