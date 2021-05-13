<?php

class Customer implements JsonSerializable
{
    private $id;
    private $name;
    private $email;
    private $dob;
    private $ipAddress;
    private $phone1;
    private $phone2;
    private $createdAt;
    private $new;
    private $vip;
    private $taxId;
    private $visitor;

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
     * Get the value of new
     */
    public function getNew()
    {
        return $this->new;
    }

    /**
     * Set the value of new
     *
     * @return  self
     */
    public function setNew($new)
    {
        $this->new = $new;

        return $this;
    }

    /**
     * Get the value of vip
     */
    public function getVip()
    {
        return $this->vip;
    }

    /**
     * Set the value of vip
     *
     * @return  self
     */
    public function setVip($vip)
    {
        $this->vip = $vip;

        return $this;
    }

    /**
     * Get the value of createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set the value of createdAt
     *
     * @return  self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the value of phone2
     */
    public function getPhone2()
    {
        return $this->phone2;
    }

    /**
     * Set the value of phone2
     *
     * @return  self
     */
    public function setPhone2($phone2)
    {
        $this->phone2 = $phone2;

        return $this;
    }

    /**
     * Get the value of phone1
     */
    public function getPhone1()
    {
        return $this->phone1;
    }

    /**
     * Set the value of phone1
     *
     * @return  self
     */
    public function setPhone1($phone1)
    {
        $this->phone1 = $phone1;

        return $this;
    }

    /**
     * Get the value of ipAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set the value of ipAddress
     *
     * @return  self
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get the value of dob
     */
    public function getDob()
    {
        return $this->dob;
    }

    /**
     * Set the value of dob
     *
     * @return  self
     */
    public function setDob($dob)
    {
        $this->dob = $dob;

        return $this;
    }

    /**
     * Get the value of email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of taxId
     */
    public function getTaxId()
    {
        return $this->taxId;
    }

    /**
     * Set the value of taxId
     *
     * @return  self
     */
    public function setTaxId($taxId)
    {
        $this->taxId = $taxId;

        return $this;
    }

    /**
     * Get the value of visitor
     */
    public function getVisitor()
    {
        return $this->visitor;
    }

    /**
     * Set the value of visitor
     *
     * @return  self
     */
    public function setVisitor($visitor)
    {
        $this->visitor = $visitor;

        return $this;
    }

}
