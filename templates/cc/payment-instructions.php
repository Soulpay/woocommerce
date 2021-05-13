<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="woocommerce-message">
    
    <?php 

    if ($status != 'on-hold') {
       printf(__('<span>Payment successfully: %s in %s.</span>', 'woocommerce-soulpay'), '<strong>' . number_format($total, 2) . '</strong>', '<strong>' . $installments . 'x</strong>');
    } else {
        printf(__('<span>Payment in manual review: %s in %s. </span>', 'woocommerce-soulpay'), '<strong>' . number_format($total, 2) . '</strong>', '<strong>' . $installments . 'x</strong>');
        printf(__('</br><span>You will receive a confirmation email shortly.</span>', 'woocommerce-soulpay'));
    }

    ?>

</div>