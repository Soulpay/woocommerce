<?php

require_once 'Request.php';

class LoginRequest extends Request
{

    

    public function __construct($isProduction = true)
    {
        parent::__construct("auth/login",$isProduction);
    }

    public function send($data)
    {
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $result = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $return['httpCode'] = $httpcode;
            $return['response'] = $result;
            return $return;
        } catch (Exception $e) {
        }
    }

}
