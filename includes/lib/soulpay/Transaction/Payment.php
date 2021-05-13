<?php

class Payment implements JsonSerializable
{
    private $chargeTotal;
    private $currencyCode;
    private $creditInstallment;

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
     * Get the value of chargeTotal
     */
    public function getChargeTotal()
    {
        return $this->chargeTotal;
    }

    /**
     * Set the value of chargeTotal
     *
     * @return  self
     */
    public function setChargeTotal($chargeTotal)
    {
        $this->chargeTotal = $chargeTotal;

        return $this;
    }

    /**
     * Get the value of currencyCode
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * Set the value of currencyCode
     *
     * @return  self
     */
    public function setCurrencyCode($currencyCode)
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    /**
     * Get the value of creditInstallment
     */
    public function getCreditInstallment()
    {
        return $this->creditInstallment;
    }

    /**
     * Set the value of creditInstallment
     *
     * @return  self
     */
    public function setCreditInstallment(CreditInstallment $creditInstallment)
    {
        $this->creditInstallment = $creditInstallment;

        return $this;
    }

}
