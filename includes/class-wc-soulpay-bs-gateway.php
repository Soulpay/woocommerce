<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_soulpay_BS_Gateway extends WC_Payment_Gateway_CC
{

    const ID = 'soulpay-bs';

    const PROCESSING_TYPE_SALE = 'sale';
    const PROCESSING_TYPE_AUTH = 'auth';

    public $email;
    public $password;
    public $hash;
    public $expirationDate;
    public $instructions;
    public $isProduction;
    public $isEnabled;

    public $api;

    public $supports = array(
        'products'
    );

    public function __construct()
    {
        $this->id = self::ID;
        $this->method_title = __('SoulPay - BankSlip', 'woocommerce-soulpay');
        $this->method_description = __('Accept Payments by BankSlip using the SoulPay', 'woocommerce-soulpay');
        $this->has_fields = true;

        // Global Settings
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->email = $this->get_option('email');
        $this->password = $this->get_option('password');
        $this->hash = $this->get_option('hash');
        $this->expirationDate = $this->get_option('expirationDate');
        $this->instructions = $this->get_option('instructions');
        $this->isProduction = $this->get_option('environment') == 'LIVE';
        $this->isEnabled = $this->get_option('enabled') == 'yes';

        $this->init_form_fields();
        $this->init_settings();

        $this->api = new WS_soulpay_BS_API($this);

        // Front actions
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'set_thankyou_page'));
        add_action('woocommerce_email_after_order_table', array($this, 'set_email_instructions'), 10, 3);

        // Admin actions
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

    }

    public function form() {
        wp_enqueue_script( 'wc-bank-slip-form' );

        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }

        include_once WC_soulPay::get_plugin_path() . 'templates/bs/form/payment.php';
    }


    public function is_available()
    {
        return parent::is_available();
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
                'label' => __('Enable SoulPay BankSlip', 'woocommerce-soulpay'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce-soulpay'),
                'type' => 'text',
                'description' => __('Displayed at checkout.', 'woocommerce-soulpay'),
                'desc_tip' => true,
                'default' => __('BankSlip', 'woocommerce-soulpay')
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
                'default' => __('Pay your order with BankSlip.', 'woocommerce-soulpay')
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

            'bankSlipOptions' => array(
                'title' => __('Bank Slip Options', 'woocommerce-soulpay'),
                'type' => 'title',
                'description' => ''
            ),

            'expirationDate' => array(
                'title' => __('Days to Expiration Date', 'woocommerce-soulpay'),
                'type' => 'number',
                'description' => __('Number of days for expiration', 'woocommerce-soulpay'),
                'desc_tip' => true,
                'default' => 1,
            ),

            'instructions' => array(
                'title' => __('Instructions', 'woocommerce-soulpay'),
                'type' => 'text',
                'description' => __('Instruction for pay the Bank Slip', 'woocommerce-soulpay'),
                'desc_tip' => true,
                'default' => __('PAYABLE AT ANY BANK UNTIL THE MATURITY', 'woocommerce-soulpay')
            ),

        );
    }

    public function get_supported_currencies()
    {
        return apply_filters(
            'woocommerce_soulpay_supported_currencies', array(
                'BRL'
            )
        );
    }
    
    public function set_thankyou_page($order_id)
    {

        $order = new WC_Order($order_id);

        $response = get_post_meta($order->get_id(), '_soulpay_capture_result_data', true);

        wc_get_template(
            'bs/bs-payment-instructions.php',
            array(
                'BankSlipUrl' => $response['response']['bankSlipUrl'],
                'BankSlipBarCode' => $response['response']['bankSlipBarCode'],
                'BankSlipValue' => $response['response']['chargeTotal']
            ),
            'woocommerce/soulpay/',
            WC_soulPay::get_templates_path()
        );
    }
    
    public function set_email_instructions(WC_Order $order)
    {

        $response = get_post_meta($order->get_id(), '_soulpay_capture_result_data', true);

        wc_get_template(
            'bs/emails/instructions.php',
            array(
                'BankSlipUrl' => $response['response']['bankSlipUrl'],
                'BankSlipBarCode' => $response['response']['bankSlipBarCode'],
                'BankSlipValue' => $response['response']['chargeTotal']
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
        return $this->api->pay($order, $_POST);
    }

    public function updateOrder(WC_Order $order)
    {
        $this->api->updateOrder($order);

    }

}