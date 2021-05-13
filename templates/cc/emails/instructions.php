<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<h2><?php _e('Payment', 'woocommerce-soulpay'); ?></h2>
<p class="order_details"><?php printf(__('Payment successfully: %s in %s.', 'woocommerce-soulpay'), '<strong>' . number_format($total, 2) . '</strong>', '<strong>' . $installments . 'x</strong>'); ?></p>
