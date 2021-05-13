<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_soulpay_CC_Gateway extends WC_Payment_Gateway_CC
{

    const ID = 'soulpay-cc';

    const MIN_PER_INSTALLMENT = '5';

    const PROCESSING_TYPE_SALE = 'sale';
    const PROCESSING_TYPE_AUTH = 'auth';


    public $email;
    public $password;
    public $hash;
    public $max_installments;
    public $interest_rate;
    public $max_without_interest;
    public $min_per_installments;
    public $installments_default;
    public $isProduction;
    public $isEnabled;

    public $api;

    public $supports = array(
        'products',
        'subscriptions',
        'subscription_cancellation',
        'gateway_scheduled_payments'
    );

    public function __construct()
    {
        $this->id = self::ID;
        $this->method_title = __('SoulPay - Credit Card', 'woocommerce-soulpay');
        $this->method_description = __('Accept Payments by Credit Card using the SoulPay', 'woocommerce-soulpay');
        $this->has_fields = true;

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->email = $this->get_option('email');
        $this->password = $this->get_option('password');
        $this->hash = $this->get_option('hash');
        $this->max_installments = intval($this->get_option('max_installments'));
        $this->interest_rate = floatval($this->get_option('interest_rate'));
        $this->max_without_interest = intval($this->get_option('max_without_interest'));
        $this->min_per_installments = floatval($this->get_option('min_per_installments'));
        $this->installments_default = intval($this->get_option('installments_default'));
        $this->isProduction = $this->get_option('environment') == 'LIVE';
        $this->isEnabled = $this->get_option('enabled') == 'yes';

        $this->init_form_fields();
        $this->init_settings();

        $this->api = new WS_soulpay_CC_API($this);
       
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'set_thankyou_page'));
        add_action('woocommerce_email_after_order_table', array($this, 'set_email_instructions'), 10, 3);
        add_action('woocommerce_subscription_status_cancelled', array($this, 'cancel_subscription'));

        // Admin actions
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

    }

    public function form()
    {
        wp_enqueue_script( 'wc-credit-card-form' );

        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }

        include_once WC_soulPay::get_plugin_path() . 'templates/cc/form/payment.php';
    }

    public function generateInstallmentField($orderTotal) {

        $installments = $this->calculeInstallments($orderTotal);

        if($this->hasSubscription()) {
            $field = '<select id="'. esc_attr( $this->id ) . '-card-installments" style="font-size: 1.2em; padding: 4px; width: 100%; visibility: hidden;" '. $this->field_name( 'card-installments' ) .' >
                <option value="1" selected > </option>
            </select>';
        } else {
            $field = '<p class="form-row form-row-wide">
                <select id="'. esc_attr($this->id) . '-card-installments" style="font-size: 1.2em; padding: 4px; width: 100%; " '. $this->field_name('card-installments') .' >';

            foreach ($installments as $installment) {
                $selected = '';

                if ($installment->default || $isSubscription) {
                    $selected = 'selected';
                }

                $field .= '<option value="'.$installment->installment.'" '. $selected .' > '. sprintf(__('%sx de R$ %s - Total: %s ', 'woocommerce-soulpay'), $installment->installment, number_format($installment->value, 2), number_format($installment->totalValue, 2)) . $installment->isFee .' </option>';
        
                if ($isSubscription) {
                    break;
                }
            }

            $field .= '</select></p>';
        }

        return $field;
    }

    public function calculeInstallments($orderTotal) {
        $installments = [];

        for ($i = 1; $i <= $this->max_installments; $i++) {
           
            $fee = '';

            if ($i == 1 || $this->max_without_interest >= $i || $this->interest_rate <= 0) {
                $totalValue = $orderTotal;
            } else {
                $totalValue = $orderTotal * pow((1 + ($this->interest_rate / 100) ), $i);
                $fee = '*';
            }

            $value =  $totalValue / floatval($i);

            if ($this->min_per_installments > $value) {
                break;
            }

            $selected = false;

            if ($i == $this->installments_default || ($this->max_installments < $this->installments_default && $i == 1) ) {
                $selected = true;
            }

            $installments[] = (object)[
                'installment' => $i,
                'value' => $value,
                'totalValue' => $totalValue,
                'default' => $selected,
                'isFee' => $fee
            ];
        }

        return $installments;
    }

    public function is_available()
    {
        return parent::is_available() && !empty($this->email) && !empty($this->password) && !empty($this->hash) && $this->using_supported_currency();
    }

    public function admin_options()
    {
        include 'admin/views/html-admin-page.php';
    }

    public function init_form_fields()
    {
        $this->form_fields = array (
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce-soulpay'),
                'type' => 'checkbox',
                'label' => __('Enable SoulPay Credit Card', 'woocommerce-soulpay'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce-soulpay'),
                'type' => 'text',
                'description' => __('Displayed at checkout.', 'woocommerce-soulpay'),
                'desc_tip' => true,
                'default' => __('Credit Card', 'woocommerce-soulpay')
            ),

            'integration' => array(
                'title' => __('Integration Settings', 'woocommerce-soulpay'),
                'type' => 'title',
                'description' => ''
            ),

            'environment' => array(
                'title' => __('Environment', 'woocommerce-soulpay'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __('Select the environment type (test or production).', 'woocommerce-soulpay'),
                'desc_tip' => true,
                'default' => 'TEST',
                'options' => array(
                    'TEST' => __('Test', 'woocommerce-soulpay'),
                    'LIVE' => __('Production', 'woocommerce-soulpay')
                )
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce-soulpay'),
                'type' => 'textarea',
                'description' => __('Displayed at checkout.', 'woocommerce-soulpay'),
                'desc_tip' => true,
                'default' => __('Pay your order with a credit card.', 'woocommerce-soulpay')
            ),
            'email' => array(
                'title' => __('Email', 'woocommerce-soulpay'),
                'type' => 'text',
                'description' => __('SoulPay email Login', 'woocommerce-soulpay'),
                'desc_tip' => true,
            ),
            'password' => array(
                'title' => __('Password', 'woocommerce-soulpay'),
                'type' => 'password',
                'description' => __('SoulPay password Login.', 'woocommerce-soulpay'),
                'desc_tip' => true,
            ),
            'hash' => array(
                'title' => __('Hash', 'woocommerce-soulpay'),
                'type' => 'text',
                'description' => __('SoulPay hash Login', 'woocommerce-soulpay'),
                'desc_tip' => true,
            ),

            'payment' => array(
                'title' => __('Payment Options', 'woocommerce-soulpay'),
                'type' => 'title',
                'description' => ''
            ),

            'max_installments' => array(
                'title' => __('Maximum number of installments', 'woocommerce-soulpay'),
                'type' => 'select',
                'description' => __('Maximum number of installments for orders in your store.', 'woocommerce-soulpay'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => '1',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12'
                )
            ),
            'installments_default' => array(
                'title' => __('Installments Default', 'woocommerce-soulpay'),
                'type' => 'select',
                'description' => __('Installment Selected default.', 'woocommerce-soulpay'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => '1',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12'
                )
            ),
            'interest_rate' => array(
                'title' => __('Interest Rate (%)', 'woocommerce-soulpay'),
                'type' => 'text',
                'description' => __('Percentage of interest that will be charged to the customer in the installment where there is interest rate to be charged.', 'woocommerce-soulpay'),
                'desc_tip' => true,
                'default' => '0'
            ),
            'max_without_interest' => array(
                'title' => __('Number of installments without Interest Rate', 'woocommerce-soulpay'),
                'type' => 'select',
                'description' => __('Indicate the number of public without Interest Rate.', 'woocommerce-soulpay'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => '0',
                'options' => array(
                    '0' => __('None', 'woocommerce-soulpay'),
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12'
                )
            ),
            'min_per_installments' => array(
                'title' => __('Minimum value per installments', 'woocommerce-soulpay'),
                'type' => 'text',
                'description' => __('Minimum value per installments, cannot be less than 1.', 'woocommerce-soulpay'),
                'desc_tip' => true,
                'default' => self::MIN_PER_INSTALLMENT
            ),
            

        );
    }

    public function get_supported_currencies()
    {
        return apply_filters(
            'woocommerce_soulpay_supported_currencies', array(
                'BRL')
        );
    }

    public function set_thankyou_page($order_id)
    {

        $order = new WC_Order($order_id);

        $installments = get_post_meta($order->get_id(), '_soulpay_installments', true);

        wc_get_template(
            'cc/payment-instructions.php',
            array(
                'installments' => $installments->installment,
                'total' => $installments->totalValue,
                'status' => $order->get_status()
                ),
            'woocommerce/soulpay/',
            WC_soulPay::get_templates_path()
        );
    }

    public function set_email_instructions(WC_Order $order)
    {

        $installments = get_post_meta($order->get_id(), '_soulpay_installments', true);

        wc_get_template(
            'cc/emails/instructions.php',
            array(
                'installments' => $installments->installment,
                'total' => $installments->totalValue
                ),
            'woocommerce/soulpay/',
                WC_soulPay::get_templates_path()
            );
    }

    public function using_supported_currency()
    {
        return in_array(get_woocommerce_currency(), $this->get_supported_currencies());
    }

    public function process_payment($order_id)
    {

        $order = new WC_Order($order_id);

        if( $this->isSubscription($order)) {
            return $this->api->subscription($order, $_POST);
        } else {
            return $this->api->pay($order, $_POST);
        }
    }

    public function isSubscription($order) {
        return class_exists('WC_Subscriptions_Order') && wcs_order_contains_subscription($order);
    }

    public function hasSubscription() {
        if (class_exists('WC_Subscriptions_Cart')) {
            return WC_Subscriptions_Cart::cart_contains_subscription();
        }
        return false;
    }

    public function updateOrder(WC_Order $order)
    {
        $this->api->updateOrder($order);
    }

    public function cancel_subscription(WC_Subscription $subscription)
    {
        return $this->api->cancelSubscription($subscription);
    }

}