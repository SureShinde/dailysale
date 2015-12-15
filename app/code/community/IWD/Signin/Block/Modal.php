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
	    
	    $_scheme = Mage::app()->getRequest()->getScheme();
		if ($_scheme=='https'){
			$_secure = true;
		}else{
			$_secure = false;
		}
		
		$config = new Varien_Object();
		
		$config->setData('url', Mage::getBaseUrl('link', $_secure));
		
		$config->setData('isLoggedIn', (int)Mage::getSingleton('customer/session')->isLoggedIn());
		
		return $config->toJson();
	}
	
}