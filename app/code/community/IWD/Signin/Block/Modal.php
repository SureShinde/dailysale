<?php
class IWD_Signin_Block_Modal extends Mage_Core_Block_Template
{

	public function _construct(){
		$_helper = Mage::helper('signin');
		if ($_helper->isAvailableVersion()){
			$this->setTemplate(null);
		}
	}
	
	public function getConfig(){
		$isSecure = Mage::app()->getStore()->isCurrentlySecure();
		
		$config = new Varien_Object();
		
		$config->setData('url', Mage::getBaseUrl('link', $isSecure));
		
		$config->setData('isLoggedIn', (int)Mage::getSingleton('customer/session')->isLoggedIn());
		
		return $config->toJson();
	}
	
}