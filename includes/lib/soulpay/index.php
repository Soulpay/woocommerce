<?php
    namespace Soulpay;
    require_once 'Address/Billing.php';
    require_once 'Address/Shipping.php';
    require_once 'Auth/Login.php';
    require_once 'Auth/Token.php';
    require_once 'Auth/RefreshToken.php';
    require_once 'Customer/Customer.php';
    require_once 'Request/BankSlipRequest.php';
    require_once 'Request/CreditCardRequest.php';
    require_once 'Request/LoginRequest.php';
    require_once 'Request/RecurringRequest.php';
    require_once 'Request/RefreshTokenRequest.php';
    require_once 'Request/TokenRequest.php';
    require_once 'Request/TransactionRequest.php';
    require_once 'Transaction/CreditCard.php';
    require_once 'Transaction/BankSlip.php';
    require_once 'Transaction/BankSlipTransaction.php';
    require_once 'Transaction/CreditCardTransaction.php';
    require_once 'Transaction/CreditInstallment.php';
    require_once 'Transaction/OrderId.php';
    require_once 'Transaction/Payment.php';
    require_once 'Transaction/Recurring.php';
    require_once 'Transaction/RecurringTransaction.php';
    require_once 'Transaction/RecurringCancel.php';
?>
