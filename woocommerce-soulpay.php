<?php

/**
 * Plugin Name: WooCommerce SoulPay!
 * Plugin URI: 
 * Description: Plugin for SoulPay!
 * Author: Emerson Abdias 
 * Author URI: emerson_abedias@hotmail.com
 * Version: 0.0.8
 * License: 
 * License URI: 
 * Text Domain: woocommerce-soulpay
 * Domain Path: /languages/
 */

 if (!defined('ABSPATH') ) {
     exit;
 }

include_once 'includes/lib/soulpay/index.php';
include_once 'includes/lib/util/soulpayUtil.php';

if (!class_exists('WC_soulPay')) {
    
    class WC_soulPay 
    {
        const VERSION = '0.0.8';
        protected static $instance = null;
        protected $log = false;

        public static function load_soulpay_class()
        {
            if(null == self::$instance) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        private function __construct()
        {

            add_action('init', array($this, 'load_plugin_textdomain'));

            if(class_exists('WC_Payment_Gateway') && function_exists('curl_exec'))  {
                $this->includes();
                
                add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
                
                add_filter('plugin_action_links_'.plugin_basename(__FILE__), array($this, 'plugin_action_links'));

                add_action('woocommerce_api_' . strtolower(get_class($this) . '_cron'), array($this, 'execute_crons'));
                
                add_filter( 'admin_notices', array($this, 'show_notices_util'));

                add_filter('woocommerce_add_to_cart_validation', array($this, 'add_to_cart_validation'), 10, 3);
                
            } else {
                add_action('admin_notices', array($this, 'notify_dependencies_missing'));
            }

        }

        public function notify_dependencies_missing()
        {
            if (!function_exists('curl_exec')) {
                include_once 'includes/admin/views/html-missing-curl.php';
            }
            if (!class_exists('WC_Payment_Gateway')) {
                include_once 'includes/admin/views/html-missing-woocommerce.php';
            }
        }

        public function is_available()
        {
            return true;
        }

        public static function get_templates_path()
        {
            return plugin_dir_path(__FILE__) . 'templates/';
        }

        public function add_gateway($methods)
        {
            array_push($methods, 'WC_soulpay_CC_Gateway', 'WC_soulpay_BS_Gateway');
            return $methods;
        }

        private function includes()
        {
            include_once 'includes/class-wc-soulpay-cc-gateway.php';
            include_once 'includes/class-wc-soulpay-bs-gateway.php';
            include_once 'includes/class-wc-soulpay-cc-api.php';
            include_once 'includes/class-wc-soulpay-bs-api.php';
            include_once 'includes/class-ws-soulpay-cron.php';
        }

        public function plugin_action_links($links) 
        {
            $plugin_links = array();
            array_push($plugin_links, '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_soulpay_cc_gateway')) . '">' . __('Credit Card Settings', 'woocommerce-soulpay') . '</a>');
            array_push($plugin_links, '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_soulpay_bs_gateway')) . '">' . __('Bank Slip Settings', 'woocommerce-soulpay') . '</a>');
            return array_merge($plugin_links, $links);
        }

        public function load_plugin_textdomain()
        {
            $locale = apply_filters('plugin_locale', get_locale(), 'woocommerce-soulpay');
            load_textdomain('woocommerce-soulpay', trailingslashit(WP_LANG_DIR) . 'woocommerce-soulpay/woocommerce-soulpay-' . $locale . '.mo');
            load_plugin_textdomain('woocommerce-soulpay', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        public static function get_plugin_path()
        {
            return plugin_dir_path(__FILE__);
        }

        public static function get_main_file()
        {
            return __FILE__;
        }

        public function show_notices_util() 
        {
            $section = isset($_GET['section']) ? $_GET['section'] : '';
            if (strpos($section, 'soulpay') !== false) {
                include_once 'templates/admin/html-notices-util.php';
            }
        }

        public function execute_crons()
        {
            do_action('soulpay_update_cc_orders');
            do_action('soulpay_update_bs_orders');

            $paragraph = '<p>' . __('soulpay! cron\'s max', 'woocommerce-soulpay') . '</p>';
            $link = '<p><a href="' . home_url('/') . '">' . __('Click here', 'woocommerce-soulpay') . '</a>';
            $text = __('to return to shop.', 'woocommerce-soulpay') . '</p>';
            wp_die($paragraph . $link . $text);
        }

                
        private function product_is_subscription($product)
        {
            if(class_exists('WC_Product_Subscription') && class_exists('WC_Product_Variable_Subscription') && class_exists('WC_Product_Subscription_Variation'))
                return ($product instanceof WC_Product_Subscription) || ($product instanceof WC_Product_Variable_Subscription) || ($product instanceof WC_Product_Subscription_Variation);

            return false;
        }

        public function add_to_cart_validation($valid, $product_id, $quantity)
        {
            $productToAdd = wc_get_product($product_id);

            if(function_exists('wcs_is_subscription'))
                wcs_is_subscription($productToAdd);

            if($this->product_is_subscription($productToAdd))
            {
                global $woocommerce;
                $cartItems = $woocommerce->cart->get_cart();

                foreach($cartItems as $item => $values)
                {
                    $cartProduct =  wc_get_product( $values['data']->get_id() );

                    if($this->product_is_subscription($cartProduct))
                    {

                        if($productToAdd->get_id() == $cartProduct->get_id())
                            return true;

                        wc_add_notice(__('Only one subscription allowed per cart!', 'woocommerce-soulpay'), 'error');
                        return false;
                    }
                }
            }

            return true;
        }


    }

    add_action('plugins_loaded', array('WC_soulPay', 'load_soulPay_class'));
}