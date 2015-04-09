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
		$gatewayResponse = parent::checkVatNumber($countryCode, $vatNumber, $requesterCountryCode, $requesterVatNumber);
	
		$requestParams = array();
		$requestParams['countryCode'] = $countryCode;
		$requestParams['vatNumber'] = str_replace(array(' ', '-'), array('', ''), $vatNumber);
		$requestParams['requesterCountryCode'] = $requesterCountryCode;
		$requestParams['requesterVatNumber'] = str_replace(array(' ', '-'), array('', ''), $requesterVatNumber);

                $result = $gatewayResponse->getIsValid();

		// try the EU VIES website
		if (!$result && Mage::getStoreConfig('customer/vat_services/vies_validation'))
		{
			$vat_url = 'http://ec.europa.eu/taxation_customs/vies/viesquer.do?';
			$vat_url .= http_build_query(array(
                'ms'			=> $requestParams['countryCode'],
                'iso'			=> $requestParams['countryCode'],
                'vat'			=> $requestParams['vatNumber'],
                'requesterMs'   => $requestParams['requesterCountryCode'],
                'requesterIso'  => $requestParams['requesterCountryCode'],
                'requesterVat'  => $requestParams['requesterVatNumber'],
				'BtnSubmitVat'	=> 'Verify',
			), '', '&');
			
			$body = '';
			if ($ch = curl_init())
			{
				curl_setopt($ch, CURLOPT_URL, $vat_url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
				curl_setopt($ch, CURLOPT_TIMEOUT, 3);

				$body = curl_exec($ch);
				curl_close($ch);
			}
			
			if (strstr($body, '<span class="validStyle">'))
			{
				$gatewayResponse->setIsValid(true);
				$gatewayResponse->setRequestDate(date('Y/m/d H:i:s'));
				$gatewayResponse->setRequestIdentifier('');
				$gatewayResponse->setRequestSuccess(true);
			}

                        $result = $gatewayResponse->getIsValid();
		}
		
		// try the Isvat Appspot API
                if (!$result && Mage::getStoreConfig('customer/vat_services/isvat_validation'))
		{
			$vat_url = "http://isvat.appspot.com/{$requestParams['countryCode']}/{$requestParams['vatNumber']}/";
			
			$body = '';
			if ($ch = curl_init())
			{
				curl_setopt($ch, CURLOPT_URL, $vat_url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
				curl_setopt($ch, CURLOPT_TIMEOUT, 3);

				$body = curl_exec($ch);
				curl_close($ch);
			}
			
			if (strstr($body, 'true') && !strstr($body, '<html'))
			{
				$gatewayResponse->setIsValid(true);
				$gatewayResponse->setRequestDate(date('Y/m/d H:i:s'));
				$gatewayResponse->setRequestIdentifier('');
				$gatewayResponse->setRequestSuccess(true);
			}

                        $result = $gatewayResponse->getIsValid();
		}

                // Try regex
                if (!$result && Mage::getStoreConfig('customer/vat_services/regex_validation'))
                {
                    $gatewayResponse->setIsValid($this->vatRegexCheck($requestParams['vatNumber'], $requestParams['countryCode']));
                    $gatewayResponse->setRequestDate(date('Y/m/d H:i:s'));
                    $gatewayResponse->setRequestIdentifier('');
                    $gatewayResponse->setRequestSuccess(true);
                }
		
		return $gatewayResponse;
	}

    /**
     * Based on rules in http://ec.europa.eu/taxation_customs/vies/faqvies.do
     * check if nif is valid
     *
     * Source; https://ellislab.com/forums/viewthread/159799
     *
     * @param string $vat
     * @param string $country_iso
     * @return boolean
     */

    public function vatRegexCheck($vat, $country_iso)
    {
        $country_iso = strtoupper($country_iso);
        $regex = '';

        switch ($country_iso)
        {
            case 'AT':
                $regex = '/^U[0-9]{8}$/';
                break;
            case 'BE':
                $regex = '/^0?[0-9]{*}$/';
                break;
            case 'CZ':
                $regex = '/^[0-9]{8,10}$/';
                break;
            case 'DE':
                $regex = '/^[0-9]{9}$/';
                break;
            case 'CY':
                $regex = '/^[0-9]{8}[A-Z]$/';
                break;
            case 'DK':
                $regex = '/^[0-9]{8}$/';
                break;
            case 'EE':
                $regex = '/^[0-9]{9}$/';
                break;
            case 'GR':
                $regex = '/^[0-9]{9}$/';
                break;
            case 'ES':
                $regex = '/^[0-9A-Z][0-9]{7}[0-9A-Z]$/';
                break;
            case 'FI':
                $regex = '/^[0-9]{8}$/';
                break;
            case 'FR':
                $regex = '/^[0-9A-Z]{2}[0-9]{9}$/';
                break;
            case 'GB':
                $regex = '/^([0-9]{9}|[0-9]{12})~(GD|HA)[0-9]{3}$/';
                break;
            case 'HU':
                $regex = '/^[0-9]{8}$/';
                break;
            case 'IE':
                $regex = '/^[0-9][A-Z0-9\\+\\*][0-9]{5}[A-Z]$/';
                break;
            case 'IT':
                $regex = '/^[0-9]{11}$/';
                break;
            case 'LT':
                $regex = '/^([0-9]{9}|[0-9]{12})$/';
                break;
            case 'LU':
                $regex = '/^[0-9]{8}$/';
                break;
            case 'LV':
                $regex = '/^[0-9]{11}$/';
                break;
            case 'MT':
                $regex = '/^[0-9]{8}$/';
                break;
            case 'NL':
                $regex = '/^[0-9]{9}B[0-9]{2}$/';
                break;
            case 'PL':
                $regex = '/^[0-9]{10}$/';
                break;
            case 'PT':
                $regex = '/^[0-9]{9}$/';
                break;
            case 'SE':
                $regex = '/^[0-9]{12}$/';
                break;
            case 'SI':
                $regex = '/^[0-9]{8}$/';
                break;
            case 'SK':
                $regex = '/^[0-9]{10}$/';
                break;
            default:
                return FALSE;
                break;
        }

        $vat = str_replace($country_iso, '', $vat);
        return (preg_match($regex,$vat));
    }
}
