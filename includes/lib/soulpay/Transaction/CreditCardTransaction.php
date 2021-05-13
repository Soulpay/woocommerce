<?php

class CreditCardTransaction implements JsonSerializable
{
    private $referenceNum;
    private $customer;
    private $billing;
    private $shipping;
    private $creditCard;
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

    /**
     * Get the value of referenceNum
     */
    public function getReferenceNum()
    {
        return $this->referenceNum;
    }

    /**
     * Set the value of referenceNum
     *
     * @return  self
     */
    public function setReferenceNum($referenceNum)
    {
        $this->referenceNum = $referenceNum;

        return $this;
    }

    /**
     * Get the value of payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * Set the value of payment
     *
     * @return  self
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * Get the value of creditCard
     */
    public function getCreditCard()
    {
        return $this->creditCard;
    }

    /**
     * Set the value of creditCard
     *
     * @return  self
     */
    public function setCreditCard($creditCard)
    {
        $this->creditCard = $creditCard;

        return $this;
    }

    /**
     * Get the value of shipping
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * Set the value of shipping
     *
     * @return  self
     */
    public function setShipping($shipping)
    {
        $this->shipping = $shipping;

        return $this;
    }

    /**
     * Get the value of billing
     */
    public function getBilling()
    {
        return $this->billing;
    }

    /**
     * Set the value of billing
     *
     * @return  self
     */
    public function setBilling($billing)
    {
        $this->billing = $billing;

        return $this;
    }

    /**
     * Get the value of customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Set the value of customer
     *
     * @return  self
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }
}
