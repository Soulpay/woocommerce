<?php 

class SoulpayUtil
{
    public $token = null;
    public $gateway;

    public function __construct($gateway)
    {
        $this->gateway = $gateway;
    }

    public function generateToken($isProduction) 
    {
        if (!$this->token) {
            $login = new Login();
            $login->setEmail($this->gateway->email);
            $login->setPassword($this->gateway->password);
            $login->setHash($this->gateway->hash);

            $loginRequest = new LoginRequest($isProduction);

            $response = $loginRequest->send(json_encode($login));
            $response['response'] = json_decode($response['response'], true);

            $this->token = (object)[
                'type' => $response['response']['type'],
                'token' => $response['response']['token'],
                'refreshToken' => $response['response']['refreshToken'],
                'creatDate' => new DateTime('+ 15 minutes')
            ];
        } 

        $now = new DateTime();
        if ($this->token->creatDate->getTimestamp() <  $now->getTimestamp()) {
            
            $refresh = new Token();
            $refresh->setRefreshToken($this->token->refreshToken);

            $refreshRequest = new TokenRequest($this->token->token, $isProduction);
            $response = $refreshRequest->send(json_decode($refresh));

            $response['response'] = json_decode($response['response'], true);

            $this->token = (object)[
                'type' => $response['response']['type'],
                'token' => $response['response']['token'],
                'refreshToken' => $response['response']['refreshToken'],
                'creatDate' => new DateTime('+ 15 minutes')
            ];

        }

        return $this->token;
    }

    public function clean_number($number)
    {
        return preg_replace('/\D/', '', $number);
    }

    public function clean_ip_address($ipAddress)
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ipAddress;
        }

        return '127.0.0.1';
    }

    public function createCustomer($order, $post) {
        $customer = new Customer();

        $customer->setId($order->get_billing_email());
        $customer->setName($order->get_formatted_billing_full_name());
        $customer->setEmail($order->get_billing_email());
        $customer->setIpAddress($this->clean_ip_address($order->get_customer_ip_address()));
        $customer->setTaxId($post[$this->gateway->id . '-card-document']);
        $customer->setPhone1($this->clean_number($order->get_billing_phone()));
        $customer->setPhone2($this->clean_number($order->get_billing_phone()));
        
        return $customer;
    }

    public function createBilling($order) {
        $billing = new Billing();

        $billing->setName($order->get_formatted_billing_full_name());
        $billing->setAddress($order->get_billing_address_1());
        $billing->setAddress2($order->get_billing_address_2());

        $billingNeighborhood = isset($order_data['billing_address']['neighborhood']) ? $order_data['billing_address']['neighborhood'] : null;

        $billing->setDistrict($billingNeighborhood ? $billingNeighborhood : 'N/A');
        $billing->setCity($order->get_billing_city());
        $billing->setState($order->get_billing_state());
        $billing->setPostalCode($this->clean_number($order->get_billing_postcode()));
        $billing->setCountry($order->get_billing_country());
        $billing->setPhone($this->clean_number($order->get_billing_phone()));
        $billing->setEmail($order->get_billing_email());

        return $billing;
    }

    public function cerateShipping($order) {
        $shipping = new Shipping();

        $shipping->setName($order->get_formatted_shipping_full_name());
        $shipping->setAddress($order->get_shipping_address_1());
        $shipping->setAddress2($order->get_shipping_address_2());

        $shippingNeighborhood = isset($order_data['shipping_address']['neighborhood']) ? $order_data['shipping_address']['neighborhood'] : null;

        $shipping->setDistrict( $shippingNeighborhood ?  $shippingNeighborhood : 'N/A');
        $shipping->setCity($order->get_shipping_city());
        $shipping->setState($order->get_shipping_state());
        $shipping->setPostalCode($order->get_shipping_postcode());
        $shipping->setCountry($order->get_shipping_country());
        $shipping->setPhone($this->clean_number($order->get_billing_phone()));
        $shipping->setEmail($order->get_billing_email());

        return $shipping;
    }

    public function checkDocument($document) {

        $cpfInvalidos = array(
            '00000000000',
            '11111111111',
            '22222222222',
            '33333333333',
            '44444444444',
            '55555555555',
            '66666666666',
            '88888888888',
            '99999999999',
        );

        $cnpjInvalidos = array(
            '00000000000000',
            '11111111111111',
            '22222222222222',
            '33333333333333',
            '44444444444444',
            '55555555555555',
            '66666666666666',
            '88888888888888',
            '99999999999999',
        );

        if (empty($document) || $document === '') {
            return false;
        }

        $document = $this->clean_number($document);

        if (strlen($document) == 11) {
            if (in_array($document, $cpfInvalidos)) {
                return false;
            } else {
                for ($t = 9; $t < 11; $t++) {
                    for ($d = 0, $c = 0; $c < $t; $c++) {
                        $d += $document[$c] * (($t + 1) - $c);
                    }
                    $d = ((10 * $d) % 11) % 10;
                    if ($document[$c] != $d) {
                        return false;
                    }
                }
            }
            return true;
        } else if (strlen($document) == 14) {
            if (in_array($document, $cnpjInvalidos)) {
                return false;
            } else {
                for ($t = 12; $t < 14; $t++) {
                    for ($d = 0, $m = ($t - 7), $i = 0; $i < $t; $i++) {
                        $d += $document[$i] * $m;
                        $m = ($m == 2 ? 9 : --$m);
                    }
                    $d = ((10 * $d) % 11) % 10;
                    if ($document[$i] != $d) {
                        return false;
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }

}