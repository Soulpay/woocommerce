<?php

if (!defined('ABSPATH')) {
    exit;
}

class WS_soulpay_CC_API
{

    private $gateway;
    private $util;

    public function __construct($gateway = null) 
    { 
        $this->gateway = $gateway;
        $this->util = new SoulpayUtil($gateway);
    }

    public function pay($order, $post)
    {

        $result = array(
            'result' => 'fail',
            'redirect' => ''
        );
        
        if (!isset($post['soulpay-cc-card-document']) || !$this->util->checkDocument($post['soulpay-cc-card-document'])) {
            wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . esc_html(__('Please type the valid document number.', 'woocommerce-soulpay')), 'error');
        } else {
            $creditCard = new CreditCard();

            $creditCard->setCardHolderName($post['soulpay-cc-card-name']);
            $creditCard->setNumber($this->util->clean_number($post['soulpay-cc-card-number']));
            $creditCard->setExpDate(preg_replace('/\s/', '', $post['soulpay-cc-card-expiry']));
            $creditCard->setCvvNumber($post['soulpay-cc-card-cvc']);

            $creditInstallment = new CreditInstallment();

            $installment = intval($post['soulpay-cc-card-installments']);

            $creditInstallment->setNumberOfInstallments($installment);
            $creditInstallment->setChargeInterest('N');

            $resultInstlments = $this->gateway->calculeInstallments($order->get_total());
            $chargeValue = $resultInstlments[$installment - 1];

            $fee = new WC_Order_Item_Fee();
            $fee->set_name(__("Fee installment", 'woocommerce-soulpay'));
            $fee->set_amount($chargeValue->totalValue - $order->get_total());
            $fee->set_total($chargeValue->totalValue - $order->get_total());
            
            $order->add_item($fee);
            
            $order->calculate_totals();

            $payment = new Payment();

            $payment->setChargeTotal(floatval(number_format($chargeValue->totalValue, 2)));
            $payment->setCurrencyCode($order->get_currency());
            $payment->setCreditInstallment($creditInstallment);

            $creditCardTransaction = new CreditCardTransaction();

            $creditCardTransaction->setReferenceNum($order->get_id());
            $creditCardTransaction->setCustomer($this->util->createCustomer($order, $post));
            $creditCardTransaction->setBilling($this->util->createBilling($order));
            $creditCardTransaction->setShipping($this->util->cerateShipping($order));
            $creditCardTransaction->setCreditCard($creditCard);
            $creditCardTransaction->setPayment($payment);

            // Passar o token JWT aqui.
            $this->util->generateToken($this->gateway->isProduction);
        
            if (!$this->util->token->token) {
                wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . wc_clean(__('Soulpay Invalid Credentials', 'woocommerce-soulpay')), 'error');
            }

            $request = new CreditCardRequest($this->util->token->token, $this->gateway->isProduction);

            $payment = json_decode($request->send(json_encode($creditCardTransaction)), true);
            
            $payment['response'] = json_decode($payment['response'], true);
            
            update_post_meta($order->get_id(), '_soulpay_request_data', $request);

            if ($payment['httpCode'] >= 400 ||$payment['response']['apiResponse'] == 'DENIED' || $payment['response']['apiResponse'] == 'DECLINED') {
               
                update_post_meta($order->get_id(), 'error_soulpay', json_encode($payment['response']));
                update_post_meta($order->get_id(), 'error_http_code', json_encode($payment['httpCode']));

                wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . wc_clean(__('Purchase failed, check card details', 'woocommerce-soulpay')), 'error');
            } else {
                foreach ($payment['response'] as $key => $value) {
                    update_post_meta($order->get_id(), $key, $value);
                }
    
                update_post_meta($order->get_id(), '_soulpay_capture_result_data', $payment);

                update_post_meta($order->get_id(), '_soulpay_installments', $chargeValue);
            
                if( $payment['response']['apiResponse'] == 'REVIEWING') {
                    $order->update_status('on-hold', __('Awaiting payment response from emitter', 'woocommerce-soulpay'));
                } else {
                    $order->payment_complete();
                }

                WC()->cart->empty_cart();

                return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_order_received_url()
             );
            }
        }

        return $result;
    }

    public function subscription($order, $post)
    {

        $order = wc_get_order($order->get_id());
    
        if ($order instanceof WC_Subscription) {
            $order = $order->get_parent();
        }

        $result = array(
            'result' => 'fail',
            'redirect' => ''
        );
        
        if (!isset($post['soulpay-cc-card-document']) || !$this->util->checkDocument($post['soulpay-cc-card-document'])) {
            wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . esc_html(__('Please type the valid document number.', 'woocommerce-soulpay')), 'error');
        } else {
            $creditCard = new CreditCard();

            $creditCard->setCardHolderName($post['soulpay-cc-card-name']);
            $creditCard->setNumber($this->util->clean_number($post['soulpay-cc-card-number']));
            $creditCard->setExpDate(preg_replace('/\s/', '', $post['soulpay-cc-card-expiry']));
            $creditCard->setCvvNumber($post['soulpay-cc-card-cvc']);

            $creditInstallment = new CreditInstallment();

            $creditInstallment->setNumberOfInstallments(1);
            $creditInstallment->setChargeInterest('N');

            $payment = new Payment();

            $recurring = new Recurring();

            $price = null;
            $period = null;
            $length = null;
            $interval = null;
            $trialLenght = null;
            $trialPeriod = null;
            $fee = null;

            $subscription_items = $order->get_items();

            foreach($subscription_items as $item) {
                $price = WC_Subscriptions_Product::get_price($item->get_product());
                $period = WC_Subscriptions_Product::get_period($item->get_product());
                $length = WC_Subscriptions_Product::get_length($item->get_product());
                $interval = WC_Subscriptions_Product::get_interval($item->get_product());
                $trialLenght = WC_Subscriptions_Product::get_trial_length($item->get_product());
                $trialPeriod = WC_Subscriptions_Product::get_trial_period($item->get_product());
                $fee = WC_Subscriptions_Product::get_sign_up_fee($item->get_product());
            }
            
            $payment->setChargeTotal(floatval(number_format(WC_Subscriptions_Order::get_recurring_total($order), 2)));
            $payment->setCurrencyCode($order->get_currency());
            $payment->setCreditInstallment($creditInstallment);

            $firstAmount = WC_Subscriptions_Order::get_recurring_total($order);

            $addToStartDate = $this->calculateTrialDays($trialPeriod, $trialLenght);

            if ($trialLenght > 0) {
                $firstAmount = 1;
            }

            if ($fee && $fee >= 1) {
                if ($trialLenght == 0) {
                    $firstAmount += $fee;
                } else {
                    $firstAmount = $fee;
                } 
            }

            $startDate = new DateTime();
            date_add($startDate, date_interval_create_from_date_string($addToStartDate." days"));

            $length = ($length == 0) ? 99 : $length;

            $recurring->setStartDate(date_format($startDate, 'Y-m-d'));
            $recurring->setPeriod($this->convertPeriodToSoulPay($period));
            $recurring->setFrequency(intval($interval));
            $recurring->setInstallments(strval($length / $interval));
            $recurring->setFirstAmount(floatval(number_format($firstAmount, 2)));
            $recurring->setFailureThreshold(99);

            $recurringTransaction = new RecurringTransaction();

            $recurringTransaction->setReferenceNum($order->get_id());
            $recurringTransaction->setCustomer($this->util->createCustomer($order, $post));
            $recurringTransaction->setBilling($this->util->createBilling($order));
            $recurringTransaction->setShipping($this->util->cerateShipping($order));
            $recurringTransaction->setCreditCard($creditCard);
            $recurringTransaction->setPayment($payment);
            $recurringTransaction->setRecurring($recurring);

            // Passar o token JWT aqui.
            $this->util->generateToken($this->gateway->isProduction);
        
            if (!$this->util->token->token) {
                wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . wc_clean(__('Soulpay Invalid Credentials', 'woocommerce-soulpay')), 'error');
            }

            $request = new RecurringRequest($this->util->token->token, $this->gateway->isProduction);

            $payment = json_decode($request->send(json_encode($recurringTransaction)), true);
            
            $payment['response'] = json_decode($payment['response'], true);
            
            update_post_meta($order->get_id(), '_soulpay_request_data', $request);

            if ($payment['httpCode'] >= 400 ||$payment['response']['apiResponse'] == 'DENIED' || $payment['response']['apiResponse'] == 'DECLINED') {
               
                update_post_meta($order->get_id(), 'error_soulpay', json_encode($payment['response']));
                update_post_meta($order->get_id(), 'error_http_code', json_encode($payment['httpCode']));

                wc_add_notice('<strong>' . esc_html($this->gateway->title) . '</strong>: ' . wc_clean(__('Purchase failed, check card details', 'woocommerce-soulpay')), 'error');
            } else {
                foreach ($payment['response'] as $key => $value) {
                    update_post_meta($order->get_id(), $key, $value);
                }
    
                $installment = (object)[
                    'installment' => 1,
                    'value' =>  $fee ? $fee : $price,
                    'totalValue' =>  $fee ? $fee : $price,
                    'default' => '',
                    'isFee' => ''
                ];

                update_post_meta($order->get_id(), '_soulpay_capture_result_data', $payment);

                update_post_meta($order->get_id(), '_soulpay_installments', $installment);
                
                if( $payment['response']['apiResponse'] == 'REVIEWING') {
                    $order->update_status('on-hold', __('Awaiting payment response from emitter', 'woocommerce-soulpay'));
                } else {
                    $order->payment_complete();
                }

                WC()->cart->empty_cart();

                return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_order_received_url()
             );
            }
        }

        return $result;
    }

    private function calculateTrialDays($period, $lenght) {

        if ($lenght == 0) {
            return 30;
        }

        switch ($period) {
            case 'day':
                return $lenght;
                break;
            case 'week':
                return $lenght * 7;
                break;
            case 'month':
                return $lenght * 30;
                break;
            case 'year':
                return $lenght * 365;
                break;
            default:
                return 1;
                break;
        }
    }

    private function convertPeriodToSoulPay($period) {

        switch($period) {
            case 'day':
                return 'daily';
                break;
            case 'week':
                return 'weekly';
                break;
            case 'month':
                return 'monthly';
                break;
            case 'year':
                return 'annual';
                break;
        }

    }

    public function cancelSubscription($subscription) {
        $result = array(
            'result' => 'fail',
            'redirect' => ''
        );

        $order = $subscription->get_parent();

        $orderId = get_post_meta($order->get_id(), 'orderId', 'single');

        if($orderId)
        {
            $cancel = new RecurringCancel();

            $cancel->setOrderId($orderId);

            $this->util->generateToken($this->gateway->isProduction);
        
            $request = new RecurringRequest($this->util->token->token, $this->gateway->isProduction);

            $resp = $request->delete($cancel);

            if ($resp['httpCode'] < 300) {
                $result = array(
                    'result' => 'success',
                    'redirect' => ''
                );
            }
        } 

        return $result;
    }

    public function updateOrder(WC_Order $order) {

        $paymentId = intval(get_post_meta($order->get_id(), 'orderId', true));

        $this->util->generateToken($this->gateway->isProduction);

        $request = new CreditCardRequest($this->util->token->token, $this->gateway->isProduction);
        
        $payment = $request->get($paymentId);

        $payment['response'] = json_decode($payment['response'], true);

        switch (intval($payment['response']['status'])) {
            case 1: //APROVED
                update_post_meta($order->get_id(), 'responseMessage', 'CAPTURED');
                $order->payment_complete();
                break;
            case 2: //DANIED
            case 7: //CAPTURE FAIL
            case 8: //RECURRENCE FAIL
                $order->update_status('failed', __('Payment Refused', 'woocommerce-soulpay'));
                break;
            case 3: //ANALYSIS
            case 4: //NOT REPORTED
            case 5: //PROCESSING
            case 6: //IN TROUBLESHOOTING
            case 9: //CREATED
                break;
        }
    }

}