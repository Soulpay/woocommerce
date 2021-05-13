<?php

if (!defined('ABSPATH')) {
    exit;
}

class WS_soulpay_BS_API
{

    private $util;
    private $gateway;

    public function __construct($gateway = null) 
    { 
        $this->gateway = $gateway;
        $this->util = new SoulpayUtil($gateway);
    }

    public function pay($order, $post) {

        $result = array (
            'result' => 'fail',
            'redirect' => ''
        );

        if (!isset($post['soulpay-bs-card-document']) || !$this->util->checkDocument($post['soulpay-bs-card-document'])) {
            wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . esc_html(__('Please type the valid document number.', 'woocommerce-soulpay')), 'error');
        } else {
            
            $bankSlip = new BankSlip();

            $expDate = date_create();
            for($i = 0; $i < $this->gateway->get_option('expirationDate'); $i++) {
                do {
                    date_add($expDate, date_interval_create_from_date_string("1 day"));
                } while (date_format($expDate, "w") == '0' || date_format($expDate, "w") == '6' || $this->isFeriado($expDate));
                        //Sun/0 = Domingo, Sat/6 = Sabado
            }

            $bankSlip->setExpirationDate(date_format($expDate, "Y-m-d"));
            $bankSlip->setInstructions($this->gateway->get_option('instructions'));

            $payment = new Payment();
            $payment->setChargeTotal(floatval($order->get_total()));
            $payment->setCurrencyCode($order->get_currency());

            $bankSlipTransaction = new BankSlipTransaction();
            $bankSlipTransaction->setReferenceNum($order->get_id());
            $bankSlipTransaction->setCustomer($this->util->createCustomer($order, $post));
            $bankSlipTransaction->setBilling($this->util->createBilling($order));
            $bankSlipTransaction->setBankSlip($bankSlip);
            $bankSlipTransaction->setPayment($payment);

            //Gerando um Token JWT pro request
            $this->util->generateToken($this->gateway->isProduction);

            if (!$this->util->token->token) {
                wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . wc_clean(__('Soulpay Invalid Credentials', 'woocommerce-soulpay')), 'error');
            }

            $request = new BankSlipRequest($this->util->token->token, $this->gateway->isProduction);

            $payment = json_decode($request->send(json_encode($bankSlipTransaction)), true);

            $payment['response'] = json_decode($payment['response'], true);

            if ($payment['httpCode'] >= 400 || $payment['response']['apiResponse'] == 'DECLINED' || $payment['response']['apiResponse'] == 'DENIED') {
                update_post_meta($order->get_id(), 'error_soulpay', json_encode($payment['response']));
                update_post_meta($order->get_id(), 'error_http_code', json_encode($payment['httpCode']));
                wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . wc_clean(__('Purchase failed, check your bank slip details', 'woocommerce-soulpay')), 'error');
            } else {
                foreach ($payment['response'] as $key => $value) {
                    update_post_meta($order->get_id(), $key, $value);
                }
            
                update_post_meta($order->get_id(), '_soulpay_capture_result_data', $payment);

                update_post_meta($order->get_id(), 'responseMessage', 'CAPTURED');

                $order->update_status('on-hold', __('Awaiting bank slip payment', 'woocommerce-soulpay'));
                WC()->cart->empty_cart();

                return array(
                    'result' => 'success',
                    'redirect' => $order->get_checkout_order_received_url()
                );
            }
            
        }
        return $result;
    }

    function isFeriado($data) {
        $ano = intval(date_format($data, "Y"));

        // Limite de 1970 ou após 2037 da easter_date PHP consulta http://www.php.net/manual/pt_BR/function.easter-date.php
        $pascoa = easter_date($ano);
        $dia_pascoa = date('j', $pascoa);
        $mes_pascoa = date('n', $pascoa);
        $ano_pascoa = date('Y', $pascoa);

        $feriados = array(
            // Datas Fixas dos feriados Nacional Basileira
            mktime(0, 0, 0, 1, 1, $ano), // Confraternização Universal - Lei nº 662, de 06/04/49
            mktime(0, 0, 0, 4, 21, $ano), // Tiradentes - Lei nº 662, de 06/04/49
            mktime(0, 0, 0, 5, 1, $ano), // Dia do Trabalhador - Lei nº 662, de 06/04/49
            mktime(0, 0, 0, 9, 7, $ano), // Dia da Independência - Lei nº 662, de 06/04/49
            mktime(0, 0, 0, 10, 12, $ano), // N. S. Aparecida - Lei nº 6802, de 30/06/80
            mktime(0, 0, 0, 11, 2, $ano), // Todos os santos - Lei nº 662, de 06/04/49
            mktime(0, 0, 0, 11, 15, $ano), // Proclamação da republica - Lei nº 662, de 06/04/49
            mktime(0, 0, 0, 12, 25, $ano), // Natal - Lei nº 662, de 06/04/49

            // Essas Datas depem diretamente da data de Pascoa
            // mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 48, $ano_pascoa), //2ºferia Carnaval
            mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 47, $ano_pascoa), //3ºferia Carnaval
            mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 2, $ano_pascoa), //6ºfeira Santa
            mktime(0, 0, 0, $mes_pascoa, $dia_pascoa, $ano_pascoa), //Pascoa
            mktime(0, 0, 0, $mes_pascoa, $dia_pascoa + 60, $ano_pascoa), //Corpus Cirist

        );

        return in_array(strtotime(date_format($data, "d M Y")), $feriados);
    }

    public function updateOrder(WC_Order $order) {

        $paymentId = intval(get_post_meta($order->get_id(), 'orderId', true));

        $hoje = date_create();
        $vencimento = date_create(get_post_meta($order->get_id(), 'bankSlipExpDate', true));
        $diferenca = intval(date_diff($hoje, $vencimento, false)->format("%r%a"));

        if($diferenca < -2) {
            //passou do vencimento
            $order->update_status('failed', 'Boleto expirado');
        } else {
            $this->util->generateToken($this->gateway->isProduction);

            $request = new BankSlipRequest($this->util->token->token, $this->gateway->isProduction);

            $payment = $request->get($paymentId);
            $payment['response'] = json_decode($payment['response'], true);

            if(intval($payment['response']['isRevoked']) == 1) {
                $order->update_status('failed', __('Bank slip expired', 'woocommerce-soulpay'));
            } else if(intval($payment['response']['paid']) == 1) {
                $order->payment_complete();
            }
        }
        update_post_meta($order->get_id(), 'responseMessage', 'CAPTURED');
    }

}