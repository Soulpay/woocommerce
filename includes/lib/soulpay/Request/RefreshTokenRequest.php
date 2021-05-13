<?php

require_once 'TokenRefreshRequest.php';

class RefreshTokenRequest extends TokenRefreshRequest
{

    public function __construct($jwt,$isProduction = true)
    {
        parent::__construct("auth/new-refresh-token", $jwt, $isProduction);
    }

    public function send($data)
    {
        return json_encode(parent::send($data));
    }

}
