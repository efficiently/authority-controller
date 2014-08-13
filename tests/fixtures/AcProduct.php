<?php

class AcProduct
{

    protected $name;

    protected $price;

    public function setName($value)
    {
        $this->name = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isNamed()
    {
        return !! $this->name;
    }

    public function setPrice($value)
    {
        $this->price = $value;
    }

    public function getPrice()
    {
        return $this->price;
    }
}
