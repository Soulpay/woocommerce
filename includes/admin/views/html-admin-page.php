<?php 

if (!defined('ABSPATH')) {
    exit;
}
?>
<h3><?php echo esc_html($this->method_title); ?></h3>
<?php
if ('yes' == $this->get_option('enable')) {
    if(!$this->using_supported_currency() && !class_exists('woocommerce_wpml')) {
        include 'html-notice-currency-not-supported.php';
    }
}
?>
<?php echo wpautop($this->method_description); ?>
<table class="form-table">
    <?php $this->generate_settings_html(); ?>
</table>
