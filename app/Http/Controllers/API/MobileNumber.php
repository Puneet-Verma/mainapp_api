<?php

namespace App\Http\Controllers\API;


class MobileNumber 
{
    public $countryCode;
    public $nationalMobileNumber;
    public function setCountryCode($value)
    {
        $this->countryCode= $value;
    }

    public function getCountryCode()
    {
        return $this->countryCode;
    }

    public function setNationalMobileNumber($value)
    {
        $this->nationalMobileNumber= $value;
    }

    public function getNationalMobileNumber()
    {
        return $this->nationalMobileNumber;
    }
}