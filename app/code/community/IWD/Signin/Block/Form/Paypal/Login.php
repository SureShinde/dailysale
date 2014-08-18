<?php
class Paypalauth_Identity_Block_Form_Paypal_login extends Mage_Core_Block_Template{

 
    protected function _toHtml()
    {
        $isExtensionEnabled = Mage::getStoreConfigFlag('customer/startup/paypalauth_enabled');
        if ($isExtensionEnabled) {
            return parent::_toHtml();
        }
        return '';
    }
	
	public function getPayPalButtonUrl()
	{
		return Mage::helper('signin/paypal')->getPayPalButtonUrl();
	}

}
