<?php

class Redkiwi_Rkvatfallback_Model_Service_Vatlayer implements Redkiwi_Rkvatfallback_Model_Service_ServiceInterface
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
    public function validateVATNumber(string $vatNumber, string $countryIso2)
    {
        if(!$accessKey = $this->config->getConfigVatLayerApiToken()) { // no api token set in config
            return false;
        }

        $curlHandle = curl_init('http://apilayer.net/api/validate?' . http_build_query([
            'access_key' => $accessKey,
            'vat_number' => $countryIso2 . $vatNumber,
            'format' => 1
        ]));

        // could not create a cURL request
        if(!$curlHandle) {
            return false;
        }

        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($curlHandle);
        curl_close($curlHandle);

        $validationResult = json_decode($json, true);
        if(json_last_error() !== JSON_ERROR_NONE) { // no valid JSON output form the API
            return false;
        }

        if(isset($validationResult['valid'])) { // JSON contains a valid flag
            return (bool)$validationResult['valid'];
        }

        return false;
    }
}