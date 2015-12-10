<?php
class IWD_AddressVerification_Model_Verification
{
	protected $_help_obj;
    protected $_quote_obj;
    private $_em_ex_msg = '';
    protected $_cust_sess;
    protected $_check_sess;
    
    protected $verification_lib	= 'ups';

    public $lib_path = ''; 

    public function __construct()
    {
        $this->_help_obj	= Mage::helper('addressverification');
        $this->_check_sess = Mage::getSingleton('checkout/session');
        $this->_quote_obj	= $this->_check_sess->getQuote();
        $this->_cust_sess = Mage::getSingleton('customer/session');
        
        $this->lib_path = Mage::getBaseDir('lib').'/iwd/verification/';
    }

    public function getQuote()
    {
        return $this->_quote_obj;
    }
    
    public function getCustomerSession()
    {
        return $this->_cust_sess;
    }
    
    public function getCheckout()
    {
        return $this->_check_sess;
    }

    public function setVerificationLib($lib)
    {
    	$this->verification_lib	= $lib;
    }

    public function getVerificationLib()
    {
    	return $this->verification_lib;
    }
    
    public function validate_address($type = 'Billing', $data = false)
    {  
    	// for testing 
/*
    	$candidates	= array();
    	
		$us_states	= array();
		$states = Mage::getModel('directory/country')->load('US')->getRegions(); 
		foreach ($states as $state)
			$us_states[$state->getCode()] = $state->getId();
    	
		$add['street'] = '6801 HOLLYWOOD BUELWARD 2';
		$add['city']	= 'LOS ANGELES 2';
		$add['region_abbr']	= 'CA';
		$add['region']	= $us_states[$add['region_abbr']];
		$add['postcode']	= '90028-6724 2';
		
		$candidates[] = $add;
		$candidates[] = $add;
		$candidates[] = $add;
		$candidates[] = $add;
		$candidates[] = $add;
			
   		$test_data	= array('error' => 'not valid');

    	$test_data['candidates']	= $candidates;
    	
    	$this->getCheckout()->{"set{$type}ValidationResults"}($test_data);
    	
    	return $test_data;
*/
    	// end for testing
    	
    	$lib	= $this->getVerificationLib();
    	if($lib == 'ups')
    		$results = $this->ups_validate_street_address($type, $data);
    	elseif($lib == 'usps')
    		$results = $this->usps_validate_street_address($type, $data);
    	else
    		$results = false;

    	$this->getCheckout()->{"set{$type}ValidationResults"}($results);
    	
    	return $results;
    }

    // UPS api
	public function ups_validate_street_address($type = 'Billing', $data = false)
	{
		$error	= false;

		if(!in_array($type, array('Billing', 'Shipping')) && !$data)
			return false;

		if(!$data)
		{
        	$address = $this->getQuote()->{"get{$type}Address"}();
        	$data	= $address->getData();
		}

        if(isset($data['country_id']) && !empty($data['country_id']) && $data['country_id'] == 'US')
        {
        	// skip regions
        	$state_no_ups	= array('hawaii', 'virgin islands', 'puerto rico','guam');
        	if(!empty($data['region']))
        	{
        		$reg = strtolower($data['region']);
        		if(in_array($reg, $state_no_ups))
        			return false;
        	}
        	
        	if(!empty($data['street']) && !empty($data['city']) && !empty($data['postcode']) && !empty($data['region_id']))
        	{
				$regionModel = Mage::getModel('directory/region')->load($data['region_id']); 
				$regionId = $regionModel->getCode();

				if(empty($regionId))
					return false;

				$test_mode	= (bool)Mage::getStoreConfig('addressverification/ups_address_verification/test_mode');
				$login	= Mage::getStoreConfig('addressverification/ups_address_verification/ups_login');
				$pass	= Mage::getStoreConfig('addressverification/ups_address_verification/ups_pass');
				$key	= Mage::getStoreConfig('addressverification/ups_address_verification/ups_access_key');

				/// setup config
				$GLOBALS ['ups_api'] ['access_key'] = $key;
				$GLOBALS ['ups_api'] ['developer_key'] = '';

				if($test_mode)
				{
					$GLOBALS ['ups_api'] ['server'] = 'https://wwwcie.ups.com';
					$GLOBALS ['ups_street_level_api'] ['server']	= 'https://wwwcie.ups.com';
					// in other DOCS test server should be  https://wwwcie.ups.com/webservices/XAV
				}
				else
				{
					$GLOBALS ['ups_api'] ['server'] = 'https://www.ups.com';
					$GLOBALS ['ups_street_level_api'] ['server']	= 'https://onlinetools.ups.com';
					// in other DOCS live server should be  https://onlinetools.ups.com/webservices/XAV
				}

				/** set the username and password used to connect to UPS **/
				$GLOBALS ['ups_api'] ['username'] = $login;
				$GLOBALS ['ups_api'] ['password'] = $pass;
				///////////
				
				include_once $this->lib_path.'ups/UpsAPI.php';
				include_once $this->lib_path.'ups/UpsAPI/USStreetLevelValidation.php';

$data['street'] = strip_tags($data['street']);
$data['street'] = str_replace("\r\n", " ", $data['street']);
$data['street'] = str_replace("\n\r", " ", $data['street']);
$data['street'] = str_replace("\r", " ", $data['street']);
$data['street'] = str_replace("\n", " ", $data['street']);
$data['street'] = str_replace(",", "", $data['street']);

				$check_address = array(
					'street' => $data['street'],
				    'city' => $data['city'],
				    'state' => $regionId,
				    'zip_code' => $data['postcode'],
					'country' => 'US',
				); // end address

				$customer_data = '';

				$validation = new UpsAPI_USStreetLevelValidation($check_address);
				$xml = $validation->buildRequest($customer_data);

				// returns an array
				$response = $validation->sendRequest($xml, false);

				if(isset($response['Response']))
				{
					$match_type = $validation->getMatchType();
					if($match_type == 'Unknown')
					{
						$error	= 'NO';
					}

					// get lis of addresses
					$candidates = $this->get_ups_candidates($response);
					
					if($error != 'NO')
					{
						// check if any address match for 100%
						$match = false;
						foreach($candidates as $cand)
						{
							// compare state
							if($cand['region'] != $data['region_id'])
								continue;
							
							// compare zip
							if($cand['postcode'] != $data['postcode'])
							{
								$zip_parts1 = explode('-', $cand['postcode']);
								$zip_form = str_replace(' ', '-', $data['postcode']);
								$zip_parts2 = explode('-', $zip_form);
								
								if($zip_parts1[0] != $zip_parts2[0])
									continue;
							}
	
							// from UPS
							$addr1	= strtolower($cand['street']);
							$city1	= strtolower($cand['city']);
							// from form
							$addr2	= strtolower($data['street']);
							$city2	= strtolower($data['city']);
	
							// compare street
							$p1 = strpos($addr1, $addr2);
							if($p1 === false)
								$p1 = strpos($addr2, $addr1);
							if($p1 === false)
								continue;
							
							// compare city
							$p2 = strpos($city1, $city2);
							if($p2 === false)
								$p2 = strpos($city2, $city1);
							if($p2 === false)
								continue;
							
							$match = true;
							break;
						}
						if(!$match)
							$error = 'YES';
					}

					return array('error' => $error, 'candidates' => $candidates, 'original_address' => $check_address);
				}
        	}
        }
        else
        	return false;
		
		return $error;
	}
	
	public function get_ups_candidates($response)
	{
		$valid_addresses	= array();
		
		$us_states	= array();
		$states = Mage::getModel('directory/country')->load('US')->getRegions(); 
		foreach ($states as $state)
			$us_states[$state->getCode()] = $state->getId();

		if(isset($response['AddressKeyFormat']))
		{
			$addresses_array = $response['AddressKeyFormat'];
			if(isset($addresses_array['AddressClassification']))
			{
				$valid_candidate = $this->parse_ups_candidate($addresses_array);
				if(!empty($valid_candidate))
				{
					$valid_candidate['region'] = $us_states[$valid_candidate['region_abbr']];
					$valid_addresses[] = $valid_candidate;
				}
			}
			else // we have list of addresses
			{
				foreach($addresses_array as $candidate)
				{
					$valid_candidate = $this->parse_ups_candidate($candidate);
					if(!empty($valid_candidate))
					{
						$valid_candidate['region'] = $us_states[$valid_candidate['region_abbr']];
						$valid_addresses[] = $valid_candidate;
					}
				}
			}
		}

		return $valid_addresses;
	}
	
	public function parse_ups_candidate($candidate)
	{
		if($candidate['AddressClassification']['Code'] == 0)
			return false;

		$add = array();
		if(!isset($candidate['AddressLine']))
			return false;
			
		if(is_array($candidate['AddressLine']))
			$add['street'] = implode(' ',$candidate['AddressLine']);
		else
			$add['street'] = $candidate['AddressLine'];
			
		if(!isset($candidate['PoliticalDivision2']))
			return false;
			
		$add['city']	= $candidate['PoliticalDivision2'];
					
		if(!isset($candidate['PoliticalDivision1']))
			return false;

		$add['region_abbr']	= strtoupper($candidate['PoliticalDivision1']);
		
		if(!isset($candidate['PostcodePrimaryLow']))
			return false;

		$add['postcode']	= $candidate['PostcodePrimaryLow'];
		if(isset($candidate['PostcodeExtendedLow']) && !empty($candidate['PostcodeExtendedLow']))
			$add['postcode'].= '-'.$candidate['PostcodeExtendedLow'];

		return $add;
	}
	// End UPS api
	
    // USPS api
	public function usps_validate_street_address($type = 'Billing', $data = false)
	{
		$error	= false;

		if(!in_array($type, array('Billing', 'Shipping')) && !$data)
			return false;

		if(!$data)
		{
        	$address = $this->getQuote()->{"get{$type}Address"}();
        	$data	= $address->getData();
		}

        if(isset($data['country_id']) && !empty($data['country_id']) && $data['country_id'] == 'US')
        {
        	// skip regions
        	$state_no_ups	= array('hawaii', 'virgin islands', 'puerto rico','guam');
        	if(!empty($data['region']))
        	{
        		$reg = strtolower($data['region']);
        		if(in_array($reg, $state_no_ups))
        			return false;
        	}
        	
        	if(!empty($data['street']) && !empty($data['city']) && !empty($data['postcode']) && !empty($data['region_id']))
        	{
				$regionModel = Mage::getModel('directory/region')->load($data['region_id']); 
				$regionId = $regionModel->getCode();

				if(empty($regionId))
					return false;

				$test_mode	= (bool)Mage::getStoreConfig('addressverification/usps_address_verification/test_mode');
				$key	= Mage::getStoreConfig('addressverification/usps_address_verification/usps_access_key');

				if(empty($key))
					return false;

$data['street'] = strip_tags($data['street']);
$data['street'] = str_replace("\r\n", ", ", $data['street']);
$data['street'] = str_replace("\n\r", ", ", $data['street']);
$data['street'] = str_replace("\r", ", ", $data['street']);
$data['street'] = str_replace("\n", ", ", $data['street']);
$data['street'] = str_replace(",", "", $data['street']);

				$check_address = array(
					'street' => $data['street'],
				    'city' => $data['city'],
				    'state' => $regionId,
				    'zip_code' => $data['postcode'],
					'country' => 'US',
				); // end address

				include_once $this->lib_path.'usps/USPSAddressVerify.php';
					
				$verify = new USPSAddressVerify($key);
				
				if($test_mode)
					$verify->setTestMode(true);
				else
					$verify->setTestMode(false);
				
				$usps_address = new USPSAddress;
				
				if(isset($data['company']) && !empty($data['company']))
					$usps_address->setFirmName($data['company']);
				
				$street_info	= $address->getStreet();
				$street1	= '';
				$street2	= '';
				if(is_array($street_info))
				{
					$street1	= $street_info[0];
					if(isset($street_info[1]))
						$street2	= $street_info[1];
				}
				else
					$street1	= $data['street'];

				$usps_address->setApt($street2);
				$usps_address->setAddress($street1);
				$usps_address->setCity($data['city']);
				$usps_address->setState($regionId);
				
				$zip	= trim($data['postcode']);
				$zip	= str_replace(' ','-',$zip);
				$z_p	= explode('-',$zip);
				
				$zip4	= '';
				$zip5	= $z_p[0];
				if(isset($z_p[1]) && !empty($z_p[1]))
					$zip4	= $z_p[1];
				
				$usps_address->setZip5($zip5);
				$usps_address->setZip4($zip4);
 
				$verify->addAddress($usps_address);

				// Perform the request and return result
				$verify->verify();
				$response	= $verify->getArrayResponse();

				if($verify->isSuccess())
				{
					// get lis of addresses
					$candidates = $this->get_usps_candidates($response);
					// check if candidate address is differ from entered
					if(empty($candidates))
						return array('error' => 'NO', 'candidates' => array(), 'original_address' => $check_address);

					// check if any address match for 100%
					$match = false;
					foreach($candidates as $cand)
					{
						// compare state
						if(strtolower($cand['region_abbr']) != strtolower($check_address['state']))
							continue;
						
						// compare zip
						if($cand['postcode'] != $check_address['zip_code'])
						{
							$zip_parts1 = explode('-', $cand['postcode']);
							$zip_form = str_replace(' ', '-', $check_address['zip_code']);
							$zip_parts2 = explode('-', $zip_form);
						
							if($zip_parts1[0] != $zip_parts2[0])
								continue;
						}
						
						// from USPS
						$addr1	= strtolower($cand['street']);
						$city1	= strtolower($cand['city']);
						// from form
						$addr2	= strtolower($street1);
						$city2	= strtolower($check_address['city']);
					
						// compare street
						$p1 = strpos($addr1, $addr2);
						if($p1 === false)
							$p1 = strpos($addr2, $addr1);
						if($p1 === false)
							continue;
							
						// compare city
						$p2 = strpos($city1, $city2);
						if($p2 === false)
							$p2 = strpos($city2, $city1);
						if($p2 === false)
							continue;
						
						$match = true;
						break;
					}

					if(!$match)
						$error = 'YES';

					return array('error' => $error, 'candidates' => $candidates, 'original_address' => $check_address);
				}
				else
				{
					$er_code = $verify->getErrorCode();
					$er_code = strtolower($er_code);
					if($er_code == '-2147219401')
					{
						return array('error' => 'NO', 'candidates' => array(), 'original_address' => $check_address);
					}
					elseif($er_code == '80040b1a')
					{
						return array('error' => 'API Authorization failure. User is not authorized to use API Verify.', 'candidates' => array(), 'original_address' => $check_address);
//						
					}
					elseif($er_code == '-2147219040')
					{
						return array('error' => 'This Information has not been included in this Test Server.', 'candidates' => array(), 'original_address' => $check_address);
					}
				}
        	}
        }
        else
        	return false;
		
		return $error;
	}
	
	public function get_usps_candidates($response)
	{
		$valid_addresses	= array();
		
		$us_states	= array();
		$states = Mage::getModel('directory/country')->load('US')->getRegions(); 
		foreach ($states as $state)
			$us_states[$state->getCode()] = $state->getId();

		if(isset($response['AddressValidateResponse']))
		{
			if(isset($response['AddressValidateResponse']['Address']))
			{
				$valid_candidate = $this->parse_usps_candidate($response['AddressValidateResponse']['Address']);
				if(!empty($valid_candidate))
				{
					$valid_candidate['region'] = $us_states[$valid_candidate['region_abbr']];
					$valid_addresses[] = $valid_candidate;
				}
			}
		}

		return $valid_addresses;
	}
	
	public function parse_usps_candidate($candidate)
	{
		if(!isset($candidate['Address2']) || empty($candidate['Address2']))
			return false;

		$add = array();
		$add['street'] = $candidate['Address2'];
		if(isset($candidate['Address1']) && !empty($candidate['Address1']))
			$add['street'].= ' '.$candidate['Address1'];
			
		if(!isset($candidate['City']) || empty($candidate['City']))
			return false;

		$add['city']	= $candidate['City'];
					
		if(!isset($candidate['State']) || empty($candidate['State']))
			return false;

		$add['region_abbr']	= strtoupper($candidate['State']);
		
		if(!isset($candidate['Zip5']) || empty($candidate['Zip5']))
			return false;

		$add['postcode']	= $candidate['Zip5'];
		if(isset($candidate['Zip4']) && !empty($candidate['Zip4']))
			$add['postcode'].= '-'.$candidate['Zip4'];

		return $add;
	}
	// End USPS api	
}