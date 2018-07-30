<?php

class Http {
    static function request($url) {
        //initialise a CURL session
        $connection = curl_init();
        //set the server we are using (could be Sandbox or Production server)
        curl_setopt($connection, CURLOPT_URL, $url);

        //stop CURL from verifying the peer's certificate
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);

        //set method as POST
        curl_setopt($connection, CURLOPT_POST, 0);

        //set it to return the transfer as a string from curl_exec
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        
        //Send the Request
        $response = curl_exec($connection);

        //close the connection
        curl_close($connection);

        //return the response
        return $response;
    }
}