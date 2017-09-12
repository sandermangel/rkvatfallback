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

        $diContainer = Mage::getModel('rkvatfallback/diContainer', [
            'config' => $this
        ]);
        return Mage::getModel('rkvatfallback/validator', $diContainer)->validateVatNumber($countryCode, $vatNumber);
    }

    /**
     * @return bool
     */
    public function getConfigUseVatLayer()
    {
        return (bool)Mage::getStoreConfigFlag('customer/vat_services/vatlayer_validation');
    }

    /**
     * @return bool
     */
    public function getConfigUseVies()
    {
        return (bool)Mage::getStoreConfigFlag('customer/vat_services/vies_validation');
    }

    /**
     * @return string
     */
    public function getConfigVatLayerApiToken()
    {
        return Mage::getStoreConfig('customer/vat_services/vatlayer_accesskey');
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
