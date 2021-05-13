<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php
$cron_url = add_query_arg('wc-api', 'wc_soulpay_cron', home_url('/'));
?>

<div class="updated woocommerce-message">
    <p><strong><?php echo __('soulpay! CRON', 'woocommerce-soulpay'); ?></strong></p>
    <p>
        <em>
            <a href="<?php echo $cron_url; ?>" target="_blank"><?php echo __('Click here', 'woocommerce-soulpay'); ?></a>&nbsp;
            <?php echo __('Force the execution of the CRONS soulpay!', 'woocommerce-soulpay'); ?>
        </em>
    </p>
</div>