<?php

require_once 'TransactionRequest.php';

class RecurringRequest extends TransactionRequest
{

    public function __construct($jwt, $isProduction = true)
    {
        parent::__construct("recurrence", $jwt, $isProduction);
    }

    public function send($data)
    {
        return json_encode(parent::send($data));
    }

    public function delete($data)
    {
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $this->authorization));
            curl_setopt($curl, CURLOPT_URL, $this->url.'/'.$data->getOrderId());
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $return['httpCode'] = $httpcode;
            $return['response'] = $result;
            return $return;
        } catch (Exception $e) {
        }
    }

}
