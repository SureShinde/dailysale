<?php
class IWD_Signin_Helper_Data extends Mage_Core_Helper_Abstract{
	
	const XML_PATH_ENABLE = 'signin/default/status';
	
	const XML_PATH_PAYPAL_ENABLE = "signin/paypallogin/status";
	
	const XML_PATH_FACEBOOK_ENABLE = 'signin/facebook/enable';
	const XML_PATH_FACEBOOK_APPID = 'signin/facebook/appid';
	const XML_PATH_FACEBOOK_APP_SECRET = 'signin/facebook/secret';
	const XML_PATH_FACEBOOK_SDK = 'signin_facebook_sdk';
	
	
	const XML_PATH_GOOGLE_ENABLE = 'signin/google/enable';
	const XML_PATH_GOOGLE_CLIENTID = 'signin/google/clientId';
	const XML_PATH_GOOGLE_CLIENT_SECRET = 'signin/google/clientSecret';
	const XML_PATH_GOOGLE_APP_KEY = 'signin/google/apiKey';
	
	
	const XML_PATH_YAHOO_ENABLE = "signin/yahoo/enable"; 
	const XML_PATH_YAHOO_KEY = 'signin/yahoo/consumer_key';
	const XML_PATH_YAHOO_SECRET = 'signin/yahoo/consumer_secret';
	
	
	const XML_PATH_TWITTER_ENABLE = "signin/twitter/enable";
	const XML_PATH_TWITTER_KEY = 'signin/twitter/api_key';
	const XML_PATH_TWITTER_SECRET = 'signin/twitter/api_secret';
	
	
	
	
	
	private $_version = 'CE';

	public function isEnable(){
		$status  = Mage::getStoreConfig(self::XML_PATH_ENABLE);
		return (bool) $status;
	}
	
	
	public function isLoggedIn(){
		return Mage::getSingleton('customer/session')->isLoggedIn();
	}
	

	public function getFacebookAppId(){
		$appid  = Mage::getStoreConfig(self::XML_PATH_FACEBOOK_APPID);
		return $appid;
	}
	
	public function getFacebookAppSecret(){
		$appSecret  = Mage::getStoreConfig(self::XML_PATH_FACEBOOK_APP_SECRET);
		return $appSecret;
	}
	
	public function disabledSDK(){
		$load  = Mage::getStoreConfig(self::XML_PATH_FACEBOOK_SDK);
		return (bool)$load;
	}
	
	
	public function isFacebookLoginEnable(){
		$status  = Mage::getStoreConfig(self::XML_PATH_FACEBOOK_ENABLE);
		return (bool)$status;
	}
	
	public function isPaypalLoginEnable(){
		$status  = Mage::getStoreConfig(self::XML_PATH_PAYPAL_ENABLE);
		return (bool)$status;
	}
	
	public function isAvailableVersion(){
	
		$mage  = new Mage();
		if (!is_callable(array($mage, 'getEdition'))){
			$edition = 'Community';
		}else{
			$edition = Mage::getEdition();
		}
		unset($mage);
		if ($edition=='Enterprise' && $this->_version=='CE'){
			$this->updateConfig();
			return false;
		}
		return true;
	
	}
	
	public function isGoogleLoginEnable(){
		$status  = Mage::getStoreConfig(self::XML_PATH_GOOGLE_ENABLE);
		return (bool)$status;
	}
	
	public function isYahooLoginEnable(){
		$status  = Mage::getStoreConfig(self::XML_PATH_YAHOO_ENABLE);
		return (bool)$status;
	}
	
	public function isTwitterLoginEnable(){
		$status  = Mage::getStoreConfig(self::XML_PATH_TWITTER_ENABLE);
		return (bool)$status;
	}
	
	
	public function getGoogleClientId(){
		$clientId  = Mage::getStoreConfig(self::XML_PATH_GOOGLE_CLIENTID);
		return $clientId;
	}
	
	
	public function getGoogleClientSecret(){
		$clientSecret  = Mage::getStoreConfig(self::XML_PATH_GOOGLE_CLIENT_SECRET);
		return $clientSecret;
	}
	
	public function getGooglServerApiKey(){
		$serverApiKey  = Mage::getStoreConfig(self::XML_PATH_GOOGLE_APP_KEY);
		return $serverApiKey;
	}

	
	
	private function updateConfig(){
		
		// Disable its output as well (which was already loaded)
		$message = 'IWD Social Media Login module is available for Magento CE only.<br />You are using Enterprise version of Magento.
					<br />Please obtain Enteprise copy of the modul at <a href="www.iwdextensions.com" target="_blank">www.iwdextensions.com</a></span>';
		
		Mage::getSingleton('adminhtml/session')->addNotice($message);
		
		
		$nodePath = "modules/IWD_Signin/active";
		Mage::getConfig()->setNode($nodePath, 'false', true);
		
	}
	
}
