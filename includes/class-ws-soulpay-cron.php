<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Soulpay_Cron
{

    public function __construct()
    {
        
        if ( !wp_next_scheduled( 'soulpay_update_cc_orders' )) {
            wp_schedule_event( time(), 'hourly', 'soulpay_update_cc_orders' );
        }

        if ( !wp_next_scheduled( 'soulpay_update_bs_orders' )) {
            $date = new DateTime("9:30:00", new DateTimeZone('America/Sao_Paulo') );

            wp_schedule_event( $date->getTimestamp(), 'twicedaily', 'soulpay_update_bs_orders' );
        }

        add_action('soulpay_update_cc_orders', array($this, 'update_cc_orders'));
        add_action('soulpay_update_bs_orders', array($this, 'update_bs_orders'));
    }

    public function update_cc_orders() {
        $this->update_orders(new WC_soulpay_CC_Gateway());
    }

    public function update_bs_orders() {
        $this->update_orders(new WC_soulpay_BS_Gateway());
    }

    public function update_orders($gateway) 
    {

        if ($gateway->isEnabled) {
        
            $queryOrders = new WP_Query(
                array(
                    'post_type' => 'shop_order',
                    'post_status' => 'wc-on-hold',
                    'posts_per_page' => -1,
                )
            );

            while ($queryOrders->have_posts()) {
            
                $queryOrders->the_post();
                $order_id = $queryOrders->post->ID;
                $order = new WC_Order($order_id);

                if ($order->get_payment_method() == $gateway->id) {
                    $gateway->updateOrder($order);
                }
            }
        }

    }
}

$cron = new WC_Soulpay_Cron();