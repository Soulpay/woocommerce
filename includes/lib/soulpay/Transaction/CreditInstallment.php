<?php

class CreditInstallment implements JsonSerializable
{
    private $numberOfInstallments;
    private $chargeInterest;

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
     * Get the value of numberOfInstallments
     */
    public function getNumberOfInstallments()
    {
        return $this->numberOfInstallments;
    }

    /**
     * Set the value of numberOfInstallments
     *
     * @return  self
     */
    public function setNumberOfInstallments($numberOfInstallments)
    {
        $this->numberOfInstallments = $numberOfInstallments;

        return $this;
    }

    /**
     * Get the value of chargeInterest
     */
    public function getChargeInterest()
    {
        return $this->chargeInterest;
    }

    /**
     * Set the value of chargeInterest
     *
     * @return  self
     */
    public function setChargeInterest($chargeInterest)
    {
        $this->chargeInterest = $chargeInterest;

        return $this;
    }
}
