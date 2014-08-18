<?php
require 'iwd/signin/Facebook/facebook.php';
class IWD_Signin_Model_Facebook
{
	private $_appid =false;
	private $_secret = false;
	
	  
	
	
	public function __construct(){
		$_helper = Mage::helper('signin');
		$this->_appid = $_helper->getFacebookAppId();
		$this->_secret = $_helper->getFacebookAppSecret();
	}
	
	
	public function getUser(){
		
		$_config = array(
				'appId' => $this->_appid,
				'secret' => $this->_secret,
				'fileUpload' => false,
				'allowSignedRequest' => false,
		);
		
		$facebook = new Facebook($_config);
		$uid = $facebook->getUser();
		
		return $uid;
	}
	
	
	
}