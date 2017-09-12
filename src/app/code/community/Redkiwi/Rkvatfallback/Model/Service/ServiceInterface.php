<?php

interface Redkiwi_Rkvatfallback_Model_Service_ServiceInterface
{
    /**
     * @param string $vatNumber
     * @param string $countryIso2
     * @return bool
     */
    public function validateVATNumber(string $vatNumber, string $countryIso2);
}