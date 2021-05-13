<?php
if (!defined('ABSPATH')) {
    exit;
}

$orderTotal = $this->get_order_total();

$fields = array();

$cvc_field = '<p class="form-row form-row-last">
        <label for="' . esc_attr( $this->id ) . '-card-cvc">' . esc_html__( 'Card code', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label>
        <input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . ' style="width:100px" />
    </p>';

$installmentsField = $this->generateInstallmentField($orderTotal);

$default_fields = array(
    'card-number-field' => '<p class="form-row form-row-wide">
            <label for="' . esc_attr( $this->id ) . '-card-number">' . esc_html__( 'Card number', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label>
            <input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
        </p>',
    'card-name-field' => '<p class="form-row form-row-wide">
           <label for="' . esc_attr( $this->id ) . '-card-name">' . esc_html__( 'Card Name', 'woocommerce-soulpay' ) . '&nbsp;<span class="required">*</span></label>
           <input id="' . esc_attr( $this->id ) . '-card-name" class="input-text wc-credit-card-form-card-name" inputmode="text" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="Nome Presente no Cartão" ' . $this->field_name( 'card-name' ) . ' />
        </p>',
    'card-document-field' => '<p class="form-row form-row-wide">
            <label for="' . esc_attr( $this->id ) . '-card-document">' . esc_html__( 'Card document', 'woocommerce-soulpay' ) . '&nbsp;<span class="required">*</span></label>
            <input id="' . esc_attr( $this->id ) . '-card-document" class="input-text wc-credit-card-form-card-document" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="CPF/CNPJ" onkeyup="maskCpfCnpjCc(value)" ' . $this->field_name( 'card-document' ) . ' />
     </p>',
    'card-installments-field' => $installmentsField,
    'card-expiry-field' => '<p class="form-row form-row-first">
            <label for="' . esc_attr( $this->id ) . '-card-expiry">' . esc_html__( 'Expiry (MM/YYYY)', 'woocommerce-soulpay' ) . '&nbsp;<span class="required">*</span></label>
            <input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . esc_attr__( 'MM / YYYY', 'woocommerce-soulpay' ) . '" ' . $this->field_name( 'card-expiry' ) . ' />
        </p>',
);
if ( ! $this->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
    $default_fields['card-cvc-field'] = $cvc_field;
}

$fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
?>

<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
    <?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
    <?php
        foreach ( $fields as $field ) {
            echo $field; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
        }
    ?>
    <?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
        <div class="clear"></div>
</fieldset>

<p class="order_details">
    <?php if (($this->interest_rate != '' && $this->interest_rate != null && $this->interest_rate > 0) && !$this->hasSubscription()) {
        printf(__('Fee of %s %% per month *', 'woocommerce-soulpay'), '<strong>' . number_format($this->interest_rate, 2) . '</strong>');
    }
    ?>
</p>

<script >
    function maskCpfCnpjCc(doc) {
        
        doc = doc.replace(/\D/g, '')
        
        if(doc.length <= 11 ) {	
            doc = doc.replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1'); 
            } else {
                doc = doc.replace(/(\d{2})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1');
            }
            
        document.getElementById('soulpay-cc-card-document').value = doc;
    }
</script>