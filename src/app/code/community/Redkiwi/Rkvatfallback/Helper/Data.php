<?php
 
class Redkiwi_Rkvatfallback_Helper_Data extends Mage_Customer_Helper_Data
{
    /**
     * Send request to VAT validation service and return validation result
     *
     * @param string $countryCode
     * @param string $vatNumber
     * @param string $requesterCountryCode
     * @param string $requesterVatNumber
     *
     * @return Varien_Object
     */
    public function checkVatNumber($countryCode, $vatNumber, $requesterCountryCode = '', $requesterVatNumber = '')
    {
        if(Mage::getStoreConfig('customer/vat_services/magento_vies_validation')) {
            $gatewayResponse = parent::checkVatNumber($countryCode, $vatNumber, $requesterCountryCode, $requesterVatNumber);
            if ($gatewayResponse->getIsValid()) { // the original service validates the request
                return $gatewayResponse;
            }
        }

        $gatewayResponse = new Varien_Object([
            'request_data' => (new DateTimeImmutable())->format('Y/m/d H:i:s'),
            'request_identifier' => '',
            'request_success' => true,
        ]);

        $vatNumber = $this->cleanVatNumber($vatNumber, $countryCode);

        // try the vatlayer service (free up to 100 requests a month)
        if (!$result && Mage::getStoreConfig('customer/vat_services/vatlayer_validation'))
        {
            /** @var Redkiwi_Rkvatfallback_Model_Service_Vatlayer $service */
            $service = Mage::getModel('rkvatfallback/service_vatlayer');
            $gatewayResponse->setIsValid($service->validateVATNumber($vatNumber, $countryCode));
            $gatewayResponse->setService('vatlayer');

            $result = $gatewayResponse->getIsValid();
        }

        // try the EU VIES website
        if (!$result && Mage::getStoreConfig('customer/vat_services/vies_validation'))
        {
            /** @var Redkiwi_Rkvatfallback_Model_Service_Vies $service */
            $service = Mage::getModel('rkvatfallback/service_vies');
            $gatewayResponse->setIsValid($service->validateVATNumber($vatNumber, $countryCode));
            $gatewayResponse->setService('vies_custom');

            $result = $gatewayResponse->getIsValid();
        }

        // Try regex
        if (!$result)
        {
            /** @var Redkiwi_Rkvatfallback_Model_Service_Regex $service */
            $service = Mage::getModel('rkvatfallback/service_regex');
            $gatewayResponse->setIsValid($service->validateVATNumber($vatNumber, $countryCode));
            $gatewayResponse->setService('regex');
        }
        
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
