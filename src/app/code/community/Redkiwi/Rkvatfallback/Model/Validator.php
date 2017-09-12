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

        /** @var Redkiwi_Rkvatfallback_Model_Cache $cache */
        $cache = Mage::getModel('rkvatfallback/cache');
        if ($cache->hasHit($countryCode.$vatNumber)) {
            return $cache->get($countryCode.$vatNumber);
        }

        // try the vatlayer service (free up to 100 requests a month)
        if ($this->config->getConfigUseVatLayer()) {
            /** @var Redkiwi_Rkvatfallback_Model_Service_Vatlayer $service */
            $service = Mage::getModel('rkvatfallback/service_vatlayer', $this->container);
            $gatewayResponse->setIsValid($service->validateVATNumber($vatNumber, $countryCode));
            $gatewayResponse->setService('vatlayer');

            if ($gatewayResponse->getIsValid()) { // VAT nr was validated
                $cache->save($countryCode.$vatNumber, $gatewayResponse);
                return $gatewayResponse;
            }
        }

        // try the EU VIES website
        if ($this->config->getConfigUseVies()) {
            /** @var Redkiwi_Rkvatfallback_Model_Service_Vies $service */
            $service = Mage::getModel('rkvatfallback/service_vies', $this->container);
            $gatewayResponse->setIsValid($service->validateVATNumber($vatNumber, $countryCode));
            $gatewayResponse->setService('vies_custom');

            if ($gatewayResponse->getIsValid()) { // VAT nr was validated
                $cache->save($countryCode.$vatNumber, $gatewayResponse);
                return $gatewayResponse;
            }
        }

        /** @var Redkiwi_Rkvatfallback_Model_Service_Regex $service */
        $service = Mage::getModel('rkvatfallback/service_regex');
        $gatewayResponse->setIsValid($service->validateVATNumber($vatNumber, $countryCode));
        $gatewayResponse->setService('regex');

        $cache->save($countryCode.$vatNumber, $gatewayResponse);

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
    public function cleanVatNumber($vatNumber, $countryCode)
    {
        $vatNrWithoutCountry = str_replace($countryCode,  '', $vatNumber);

        return str_replace([' ', '-'], ['', ''], $vatNrWithoutCountry);
    }
}