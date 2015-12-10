<?php
require_once 'iwd/signin/google/Google_Client.php';
require_once 'iwd/signin/google/contrib/Google_PlusService.php';
require_once 'iwd/signin/google/contrib/Google_Oauth2Service.php';
class IWD_Signin_Model_Google{
	
	
	private $_clientId = false;
	
	private $_clientSecret = false;
	
	private $_apiKey = false;
	
	
	public function __construct(){
		$helper = Mage::helper('signin');
		$this->_clientId = $helper->getGoogleClientId();
		$this->_clientSecret = $helper->getGoogleClientSecret();
		$this->_apiKey = $helper->getGooglServerApiKey();
	}
	
	
	public function getAuthUrl(){
		$client = new Google_Client();
		
		$client->setApplicationName("IWD Signin");
		$client->setClientId($this->_clientId);
		$client->setClientSecret($this->_clientSecret);
		$client->setRedirectUri(Mage::getBaseUrl('link', true) . 'signin/json/google/');
		$client->setDeveloperKey($this->_apiKey);
		$client->setScopes(array(
									"https://www.googleapis.com/auth/plus.me",
									"https://www.googleapis.com/auth/userinfo.profile",								
									"https://www.googleapis.com/auth/userinfo.email"
								));
		
		return $client->createAuthUrl();
	}
	
	
	
	public function auth(){
		
		try{
			
		
			$client = new Google_Client();
			
			$client->setApplicationName("IWD Signin");
			$client->setClientId($this->_clientId);
			$client->setClientSecret($this->_clientSecret);
			
			$client->setRedirectUri(Mage::getBaseUrl('link', true) . 'signin/json/google/');
			$client->setDeveloperKey($this->_apiKey);
			$client->setScopes(array("https://www.googleapis.com/auth/plus.me","https://www.googleapis.com/auth/userinfo.profile","https://www.googleapis.com/auth/userinfo.email"));
			
			$plus       = new Google_PlusService($client);
			$oauth2     = new Google_Oauth2Service($client);
			
			
			$code = Mage::app()->getRequest()->getParam('code', false);
			$client->authenticate(); // Authenticate
			Mage::getSingleton('core/session')->setGoogleToken($client->getAccessToken()); // get the access token here
		
		
		
		
			if ($client->getAccessToken()) {
				$user         = $oauth2->userinfo->get();
				$me           = $plus->people->get('me');
				
				return $user;
	
			}
		
		}catch(Exception $e){
		    Mage::getSingleton('customer/session')->addError($e->getMessage());
			Mage::logException($e);
			return false;
		}
		return false;
	}
}