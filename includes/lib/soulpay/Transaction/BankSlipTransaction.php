<?php
class BankSlipTransaction implements JsonSerializable
{
    private $referenceNum;
    private $customer;
    private $billing;
    private $bankSlip;
    private $payment;

    public function jsonSerialize()
    {
        $vars = array_filter(
            get_object_vars($this),
            function ($item) {
                // Keep only not-NULL values
                return !is_null($item);
            }
        );

        return $vars;
    }

    public function getReferenceNum()
    {
        return $this->referenceNum;
    }

    public function setReferenceNum($referenceNum)
    {
        $this->referenceNum = $referenceNum;

        return $this;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function setCustomer($customer)
    {
        $this->customer = $customer;
        return $this;
    }

    public function getBilling()
    {
        return $this->billing;
    }

    public function setBilling($billing)
    {
        $this->billing = $billing;
        return $this;
    }

    public function getBankSlip()
    {
        return $this->bankSlip;
    }

    public function setBankSlip($bankSlip)
    {
        $this->bankSlip = $bankSlip;
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;
        return $this;
    }
}
