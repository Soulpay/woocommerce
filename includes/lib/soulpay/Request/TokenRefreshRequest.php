<?php

require_once 'Request.php';

abstract class TokenRefreshRequest extends Request
{
    private $authorization;

    public function __construct($url, $jwt, $isProduction)
    {
        $this->url = $url;
        $this->jwt = $jwt;
        $this->authorization = "Authorization: Bearer " . $jwt;
        parent::__construct($this->url,$isProduction);
    }

    public function send($data)
    {
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $this->authorization));
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
