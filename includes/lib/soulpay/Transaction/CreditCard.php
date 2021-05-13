<?php

class CreditCard implements JsonSerializable
{
    private $cardHolderName;
    private $number;
    private $expDate;
    private $cvvNumber;

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
     * Get the value of cardHolderName
     */
    public function getCardHolderName()
    {
        return $this->cardHolderName;
    }

    /**
     * Set the value of cardHolderName
     *
     * @return  self
     */
    public function setCardHolderName($cardHolderName)
    {
        $this->cardHolderName = $cardHolderName;

        return $this;
    }

    /**
     * Get the value of number
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set the value of number
     *
     * @return  self
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get the value of expDate
     */
    public function getExpDate()
    {
        return $this->expDate;
    }

    /**
     * Set the value of expDate
     *
     * @return  self
     */
    public function setExpDate($expDate)
    {
        $this->expDate = $expDate;

        return $this;
    }

    /**
     * Get the value of cvvNumber
     */
    public function getCvvNumber()
    {
        return $this->cvvNumber;
    }

    /**
     * Set the value of cvvNumber
     *
     * @return  self
     */
    public function setCvvNumber($cvvNumber)
    {
        $this->cvvNumber = $cvvNumber;

        return $this;
    }
}
