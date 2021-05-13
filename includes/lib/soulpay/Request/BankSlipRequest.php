<?php

require_once 'TransactionRequest.php';

class BankSlipRequest extends TransactionRequest
{

    public function __construct($jwt,$isProduction = true)
    {   
        parent::__construct("bankSlip", $jwt, $isProduction);
    }

    public function send($data)
    {
        return json_encode(parent::send($data));
    }
}
