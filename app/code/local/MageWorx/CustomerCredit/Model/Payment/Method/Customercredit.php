<?php
/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @copyright  Copyright (c) 2012 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Customer Credit extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @author     MageWorx Dev Team
 */

class MageWorx_CustomerCredit_Model_Payment_Method_Customercredit extends Mage_Payment_Model_Method_Abstract
{
    protected $_code            = 'customercredit';
    protected $_formBlockType   = 'customercredit/payment_form';
    protected $_canRefund       = false;

    /**
     * Is Credit Availible
     * @param Mage_Sales_Model_Quote $quote
     * @return boolean
     */
    public function isAvailable($quote=null) {
        if (!Mage::getSingleton('customer/session')->getCustomerId() && !Mage::getSingleton('admin/session')->getUser()) {
            return false;
        }
        if (!$this->_getHelper()->isEnabled()) { // || $credit <= 0
            return false;
        }
        return true;
    }
    
    public function isApplicableToQuote($quote,$checks) {
        if (!Mage::getSingleton('customer/session')->getCustomerId() && !Mage::getSingleton('admin/session')->getUser()) {
            return false;
        }
        if (!$this->_getHelper()->isEnabled()) { // || $credit <= 0
            return false;
        }
        return true;
    }

    /**
     * Validate Credit
     * @return MageWorx_CustomerCredit_Model_Payment_Method_Customercredit
     */
    public function validate() {
        parent::validate();
        $errorMsg = false;

        if ($this->getInfoInstance() instanceof Mage_Sales_Model_Quote_Payment) {
            $result = $this->_checkCredit($this->getInfoInstance()->getQuote());
            if (!$result)
                $errorMsg = $this->_getHelper()->__('Not enough Credit Amount to complete this operation.');
        }
        if ($errorMsg) Mage::throwException($errorMsg);
        return $this;
    }

    /**
     * Check Credit type
     * @param Mage_Sales_Model_Quote $quote
     * @return boolean
     */
    protected function _checkCredit($quote) {
        if (!Mage::getSingleton('admin/session')->getUser()) {
            $customerId = Mage::getSingleton('customer/session')->getCustomerId();
            $websiteId  = Mage::app()->getStore()->getWebsiteId();
        } else {            
            $customerId = Mage::getSingleton('adminhtml/session_quote')->getCustomerId();
            $websiteId = Mage::app()->getStore(Mage::getSingleton('adminhtml/session_quote')->getStoreId())->getWebsiteId();
        }
        
        $flag = $this->_getHelper()->isPartialPayment($quote, $customerId, $websiteId);
        
        // -2 - hide customer credit
        // -1 - no balabce checkbox
        // 0 - no balance radio
        // 1 - checkbox (partial payment)
        // 2 - radio (full payment)
        
        if ($flag >= 1) return true; else return false;
        
    }

    /**
     * Retrieve model helper
     *
     * @return MageWorx_CustomerCredit_Helper_Data
     */
    protected function _getHelper() {
        return Mage::helper('customercredit');
    }
}