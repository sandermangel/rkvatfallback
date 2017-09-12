<?php

class Redkiwi_Rkvatfallback_Model_Validator
{
    /**
     * @var Redkiwi_Rkvatfallback_Model_DiContainer
     */
    protected $container;
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
        $this->container = $container;
        $this->config = $container->get('config');
    }

    /**
     * @param $countryCode
     * @param $vatNumber
     *
     * @return Varien_Object
     */
    public function validateVatNumber($countryCode, $vatNumber)
    {
        $gatewayResponse = new Varien_Object([
            'request_data' => (new DateTimeImmutable())->format('Y/m/d H:i:s'),
            'request_identifier' => '',
            'request_success' => true,
        ]);

        $vatNumber = $this->cleanVatNumber($vatNumber, $countryCode);

        // try the vatlayer service (free up to 100 requests a month)
        if ($this->config->getConfigUseVatLayer()) {
            /** @var Redkiwi_Rkvatfallback_Model_Service_Vatlayer $service */
            $service = Mage::getModel('rkvatfallback/service_vatlayer', $this->container);
            $gatewayResponse->setIsValid($service->validateVATNumber($vatNumber, $countryCode));
            $gatewayResponse->setService('vatlayer');

            if ($gatewayResponse->getIsValid()) { // VAT nr was validated
                return $gatewayResponse;
            }
        }

        // try the EU VIES website
        if ($this->config->getConfigUseVies()) {
            /** @var Redkiwi_Rkvatfallback_Model_Service_Vies $service */
            $service = Mage::getModel('rkvatfallback/service_vies');
            $gatewayResponse->setIsValid($service->validateVATNumber($vatNumber, $countryCode));
            $gatewayResponse->setService('vies_custom');

            if ($gatewayResponse->getIsValid()) { // VAT nr was validated
                return $gatewayResponse;
            }
        }

        /** @var Redkiwi_Rkvatfallback_Model_Service_Regex $service */
        $service = Mage::getModel('rkvatfallback/service_regex');
        $gatewayResponse->setIsValid($service->validateVATNumber($vatNumber, $countryCode));
        $gatewayResponse->setService('regex');

        return $gatewayResponse;
    }

    /**
     * Strip unwanted characters from the VAT number
     * and the country code
     *
     * @param string $vatNumber
     * @param string $countryCode
     * @return string
     */
    public function cleanVatNumber(string $vatNumber, string $countryCode)
    {
        $vatNrWithoutCountry = str_replace($countryCode,  '', $vatNumber);

        return str_replace([' ', '-'], ['', ''], $vatNrWithoutCountry);
    }
}