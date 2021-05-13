<?php

abstract class Request
{
    protected $url;

    public function __construct($url,$isProduction)
    {
        if($isProduction){
            $this->url = "https://api.portalsoulpay.com.br/api/v1/";
        }else{
            $this->url = "https://dev-api.portalsoulpay.com.br/api/v1/";
        }
        $this->url = $this->url.$url;
    }

}
