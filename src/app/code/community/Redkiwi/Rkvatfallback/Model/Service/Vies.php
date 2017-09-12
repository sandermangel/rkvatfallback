<?php

class Redkiwi_Rkvatfallback_Model_Service_Vies implements Redkiwi_Rkvatfallback_Model_Service_ServiceInterface
{
    /**
     * @param string $vatNumber
     * @param string $countryIso2
     * @return bool
     */
    public function validateVATNumber(string $vatNumber, string $countryIso2)
    {
        $curlHandle = curl_init('http://ec.europa.eu/taxation_customs/vies/viesquer.do?' . http_build_query([
                'ms' => $countryIso2,
                'iso' => $countryIso2,
                'vat' => $countryIso2 . $vatNumber,
                'requesterMs' => $this->getConfigMerchantCountry(),
                'requesterIso' => $this->getConfigMerchantCountry(),
                'requesterVat' => $this->getConfigMerchantVat(),
                'BtnSubmitVat' => 'Verify',
            ], '', '&'));


        // could not create a cURL request
        if(!$curlHandle) {
            return false;
        }

        curl_setopt($curlHandle, CURLOPT_HEADER, 0);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curlHandle, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 3);

        $body = curl_exec($curlHandle);
        curl_close($curlHandle);

        // body of API contains a valid flag
        if(false !== strpos($body, 'validStyle')) {
            return true;
        }

        return false;
    }

    /**
     * Get the webshops country
     *
     * @return string
     */
    public function getConfigMerchantCountry()
    {
        return Mage::helper('core')->getMerchantCountryCode();
    }

    /**
     * Get the webshops VAT number
     *
     * @return string
     */
    public function getConfigMerchantVat()
    {
        return Mage::helper('core')->getMerchantVatNumber();
    }
}