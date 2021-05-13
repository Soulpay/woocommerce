<?php

class BankSlip implements JsonSerializable
{
    private $expirationDate;
    private $instructions;

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
     * Get the value of expirationDate
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * Set the value of expirationDate
     *
     * @return  self
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * Get the value of instructions
     */
    public function getInstructions()
    {
        return $this->instructions;
    }

    /**
     * Set the value of instructions
     *
     * @return  self
     */
    public function setInstructions($instructions)
    {
        $this->instructions = $instructions;

        return $this;
    }
}
