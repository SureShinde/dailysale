<?php
class IWD_Signin_AccountsController extends Mage_Core_Controller_Front_Action{
	
	protected function _getSession()
	{
		return Mage::getSingleton('customer/session');
	}
	
	public function indexAction(){
		
		if (!$this->_getSession()->isLoggedIn()) {
			$this->_redirect('*/*/');
			return;
		}
		
		$this->loadLayout();
		
		$this->_initLayoutMessages('customer/session');
		$this->_initLayoutMessages('catalog/session');
		
		$this->getLayout()->getBlock('head')->setTitle($this->__('Social Media Login'));
		$this->renderLayout();	
	}
}