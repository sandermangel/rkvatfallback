<?php

class Redkiwi_Rkvatfallback_Model_Service_Vies implements Redkiwi_Rkvatfallback_Model_Service_ServiceInterface
{
    /**
     * @var Redkiwi_Rkvatfallback_Helper_Data
     */
    protected $config;

    /**
     * Redkiwi_Rkvatfallback_Model_Validator constructor.
     * @param Redkiwi_Rkvatfallback_Model_DiContainer $container
     */
    public function __construct($container)
    {
        $this->config = $container->get('config');
    }

    /**
     * @param string $vatNumber
     * @param string $countryIso2
     * @return bool
     */
    public function validateVATNumber($vatNumber, $countryIso2)
    {
        $curlHandle = curl_init('http://ec.europa.eu/taxation_customs/vies/viesquer.do?' . http_build_query([
                'ms' => $countryIso2,
                'iso' => $countryIso2,
                'vat' => $countryIso2 . $vatNumber,
                'requesterMs' => $this->config->getConfigMerchantCountry(),
                'requesterIso' => $this->config->getConfigMerchantCountry(),
                'requesterVat' => $this->config->getConfigMerchantVat(),
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

        // Check the text returned. Note: this can change so should be checked
        $isValid = null;
        $validNumber = strpos($body, 'Yes, valid VAT number');
        $invalidNumber = strpos($body, 'No, invalid VAT number');

        if ($validNumber !== false || $invalidNumber !== false) {
            $isValid = $validNumber ? true : false;
        }

        return $isValid;
    }
}
