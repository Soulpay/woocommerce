<?php

require_once 'TransactionRequest.php';

class CreditCardRequest extends TransactionRequest
{


    public function __construct($jwt, $isProduction = true)
    {
        parent::__construct("transaction", $jwt,$isProduction);
    }

    public function send($data)
    {
        return json_encode(parent::send($data));
    }

}
