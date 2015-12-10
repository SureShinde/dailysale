<?php

$path = Mage::getBaseDir('app').DS.'code'.DS.'community'.DS;
$file = 'IWD/Opc/controllers/IndexController.php';
if(file_exists($path.$file)) // load IWD OPC class
{
	require_once 'IWD/Opc/controllers/IndexController.php';
	class IWD_AddressVerification_IndexController extends IWD_Opc_IndexController
	{
	    public function getVerification()
	    {
	        return Mage::getSingleton('addressverification/verification');
	    }
	
		public function indexAction()
		{
			$this->getVerification()->getCheckout()->setShowValidationResults(false);
	
	    	// clear verification results from prevous checkout
			$this->getVerification()->getCheckout()->setShippingWasValidated(false);
			$this->getVerification()->getCheckout()->setBillingWasValidated(false);
			$this->getVerification()->getCheckout()->setSaveShippingWasValidated(false);
			$this->getVerification()->getCheckout()->setSaveBillingWasValidated(false);
	
			$this->getVerification()->getCheckout()->setBillingValidationResults(false);
			$this->getVerification()->getCheckout()->setShippingValidationResults(false);
	
			parent::indexAction();
		}    
	}
}
else // load standard class
{
	require_once 'Mage/Checkout/controllers/IndexController.php';
	class IWD_AddressVerification_IndexController extends Mage_Checkout_IndexController
	{
	}
}