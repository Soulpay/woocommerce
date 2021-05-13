<?php
if (!defined('ABSPATH')) {
    exit;
}

$orderTotal = $this->get_order_total();

$fields = array(
    'card-document-field' => '<p class="form-row form-row-wide">
        <label for="' . esc_attr( $this->id ) . '-card-document">' . esc_html__( 'BankSlip Document', 'woocommerce-soulpay' ) . '&nbsp;<span class="required">*</span></label>
        <input id="' . esc_attr( $this->id ) . '-card-document" class="input-text wc-bank-slip-form-card-document" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="CPF/CNPJ" onkeyup="maskCpfCnpjBs(value)" ' . $this->field_name( 'card-document' ) . ' />
    </p>'  
);
?>

<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-bs-form" class='wc-bank-slip-form wc-payment-form'>
    <?php do_action( 'woocommerce_bank_slip_form_start', $this->id ); ?>
    <?php
        foreach ( $fields as $field ) {
            echo $field; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
        }
    ?>
    <?php do_action( 'woocommerce_bank_slip_form_end', $this->id ); ?>
    <div class="clear"></div>
</fieldset>

<script >
    function maskCpfCnpjBs(doc) {
        
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
            
        document.getElementById('soulpay-bs-card-document').value = doc;
    }
</script>