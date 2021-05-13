<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="woocommerce-message">
<span>
    <?php 
        printf('%s %s %s %s', 
            '<strong> ' . __('Bank slip successfully generated', 'woocommerce-soulpay') . '</strong></br>',
            '
                <br>
                <br>
                <center><button onclick="abrirBoleto()"> ' . __('Open Bank slip', 'woocommerce-soulpay') . '</button></center></br>
                <script>
                    function abrirBoleto() {
                        window.open("'.$BankSlipUrl.'", "_blank");
                    }
                </script>
            ',
            '
                <label for="codigoBoleto">' . __('Bank slip Code', 'woocommerce-soulpay') . ': </label>
                <input id="codigoBoleto" value="' . $BankSlipBarCode . '" size="' . strlen($BankSlipBarCode) . '" disabled>
                <input type="button" value="Copiar" onclick="copyToClipboard()">
                <script>
                    function copyToClipboard() {
                        let copyText = document.getElementById("codigoBoleto");
                        copyText.disabled = false;
                        copyText.select();
                        copyText.setSelectionRange(0, copyText.value.length);
                        document.execCommand("copy");
                        copyText.disabled = true;
                    }
                </script>
            ',
            '<p>'. __('Payment amount: R$', 'woocommerce-soulpay'). ' ' . number_format($BankSlipValue, 2) . '</p></br>'
        );
    ?>
</span>
</div>