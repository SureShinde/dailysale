<?php
class IWD_Signin_Block_Accounts extends Mage_Core_Block_Template{
	
	
	public function getLinks(){
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$collection = Mage::getModel('signin/related')->getCollection()->addFieldToFilter('customer_id', array('eq'=>$customer->getId()));
		return $collection;
	}
	
}