<?php
 
class Redkiwi_Rkvatfallback_Helper_Data extends Mage_Customer_Helper_Data
{

    const VATLAYER_BASE_URL = 'http://apilayer.net/api/';

    public $forceRegexCheck = false;

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
        $result = false;
        if(Mage::getStoreConfig('customer/vat_services/magento_vies_validation')) {
            $gatewayResponse = parent::checkVatNumber($countryCode, $vatNumber, $requesterCountryCode, $requesterVatNumber);
            $result = $gatewayResponse->getIsValid();
        } else {
            $gatewayResponse = new Varien_Object;
        }

        $vatNumber = $this->cleanVatNumber($vatNumber);
        $DateTimeStamp = new DateTimeImmutable();

        // Set request parameters for the various services
        $requestParams = array();
        $requestParams['countryCode'] = $countryCode;
        $requestParams['vatNumber'] = $vatNumber;
        $requestParams['requesterCountryCode'] = $requesterCountryCode;
        $requestParams['requesterVatNumber'] = $this->cleanVatNumber($requesterVatNumber);

        // try the vatlayer service (free up to 100 requests a month)
        if (!$result && Mage::getStoreConfig('customer/vat_services/vatlayer_validation'))
        {
            $gatewayResponse->setIsValid($this->vatlayerCheck($requestParams));
            $gatewayResponse->setRequestDate($DateTimeStamp->format('Y/m/d H:i:s'));
            $gatewayResponse->setRequestIdentifier('');
            $gatewayResponse->setRequestSuccess(true);
            $gatewayResponse->setService('vatlayer');

            $result = $gatewayResponse->getIsValid();
        }

        // try the EU VIES website
        if (!$result && Mage::getStoreConfig('customer/vat_services/vies_validation'))
        {
            $gatewayResponse->setIsValid($this->vatViesCheck($requestParams));
            $gatewayResponse->setRequestDate($DateTimeStamp->format('Y/m/d H:i:s'));
            $gatewayResponse->setRequestIdentifier('');
            $gatewayResponse->setRequestSuccess(true);
            $gatewayResponse->setService('vies_custom');

            $result = $gatewayResponse->getIsValid();
        }
        
        // Try regex
        if ($this->forceRegexCheck || (!$result && Mage::getStoreConfig('customer/vat_services/regex_validation')))
        {
            /** @var Redkiwi_Rkvatfallback_Model_Service_Regex $service */
            $service = Mage::getModel('rkvatfallback/service_regex');
            $gatewayResponse->setIsValid($service->validateVATNumber($vatNumber, $countryCode));
            $gatewayResponse->setRequestDate($DateTimeStamp->format('Y/m/d H:i:s'));
            $gatewayResponse->setRequestIdentifier('');
            $gatewayResponse->setRequestSuccess(true);
            $gatewayResponse->setService('regex');
        }
        
        return $gatewayResponse;
    }

    /**
     * @param $requestParams
     * @param $gatewayResponse
     */
    protected function vatViesCheck($requestParams)
    {
        $vat_url = 'http://ec.europa.eu/taxation_customs/vies/viesquer.do?';
        $vat_url .= http_build_query(array(
            'ms' => $requestParams['countryCode'],
            'iso' => $requestParams['countryCode'],
            'vat' => $requestParams['vatNumber'],
            'requesterMs' => $requestParams['requesterCountryCode'],
            'requesterIso' => $requestParams['requesterCountryCode'],
            'requesterVat' => $requestParams['requesterVatNumber'],
            'BtnSubmitVat' => 'Verify',
        ), '', '&');

        $body = '';

        $ch = curl_init();
        if ($ch) {
            curl_setopt($ch, CURLOPT_URL, $vat_url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);

            $body = curl_exec($ch);
            curl_close($ch);
        }

        if(strstr($body, 'invalidStyle')) {
            return false;
        } elseif (strstr($body, 'validStyle')) {
            return true;
        } else {
            // Error occurred, like IP blocked, force regex check
            $this->forceRegexCheck = true;
            return false;
        }
    }

    protected function vatlayerCheck($requestParams)
    {
        $accessKey = Mage::getStoreConfig('customer/vat_services/vatlayer_accesskey');
        if(!$accessKey) return false;

        $params = array(
            'access_key' => $accessKey,
            'vat_number' => $requestParams['countryCode'] . $requestParams['vatNumber'],
            'format' => 1
        );

        $url = self::VATLAYER_BASE_URL . '/validate?' . http_build_query($params);

        $ch = curl_init($url);
        if($ch) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $json = curl_exec($ch);
            curl_close($ch);

            if($json) {
                $validationResult = json_decode($json, true);
                if(isset($validationResult['valid'])) {
                    return $validationResult['valid'];
                }
                // When the API returns an error, force regex check as fallback when custom VIES validation is disabled
                if(
                    isset($validationResult['error'])
                    && isset($validationResult['error']['code'])
                    && !Mage::getStoreConfig('customer/vat_services/vies_validation')
                ) {
                    $this->forceRegexCheck = true;
                }
            }
        }
        return false;
    }

    /**
     * Strip unwanted characters from the VAT number
     *
     * @param string $vatNumber
     * @return string
     */
    public function cleanVatNumber(string $vatNumber)
    {
        return str_replace([' ', '-'], ['', ''], $vatNumber);
    }
}
