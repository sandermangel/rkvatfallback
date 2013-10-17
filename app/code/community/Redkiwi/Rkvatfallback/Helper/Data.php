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
		
		// try the EU VIES website
		if (!$gatewayResponse->getIsValid())
		{
			$vat_url = 'http://ec.europa.eu/taxation_customs/vies/viesquer.do?';
			$vat_url .= http_build_query(array(
				'ms'			=> $requestParams['requesterCountryCode'],
				'iso'			=> $requestParams['countryCode'],
				'vat'			=> $requestParams['vatNumber'],
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
		}
		
		// try the Isvat Appspot API
		if (!$gatewayResponse->getIsValid())
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
			
			if (strstr($body, 'true'))
			{
				$gatewayResponse->setIsValid(true);
				$gatewayResponse->setRequestDate(date('Y/m/d H:i:s'));
				$gatewayResponse->setRequestIdentifier('');
				$gatewayResponse->setRequestSuccess(true);
			}
		}
		
		return $gatewayResponse;
	}
}