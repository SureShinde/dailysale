<?php
/**
 * @author		Sashas
 * @category    Sashas
 * @package     Sashas_ORderSuccessPage
 * @copyright   Copyright (c) 2013 Sashas IT Support Inc. (http://www.sashas.org)
 * @license     http://opensource.org/licenses/GPL-3.0  GNU General Public License, version 3 (GPL-3.0)

 */

class Sashas_Ordersuccesspage_Block_Success extends Mage_Checkout_Block_Onepage_Success
{
	 protected function _construct()
	{		
		parent::_construct();
		$orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
		$order_info = Mage::getModel('sales/order')->load($orderId);
		Mage::register('current_order',$order_info);		
		$this->setCanPrintOrder(true);
		$this->setCanViewOrder(true);				  		 
	}

	protected function _beforeToHtml()
	{
 
		parent::_beforeToHtml();	 
		$this->setTemplate('ordersuccesspage/success.phtml');
	}	
 
}
