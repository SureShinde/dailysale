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

class MageWorx_CustomerCredit_RefferalsController extends Mage_Core_Controller_Front_Action {

    private $_customerCredit = 0;
    
    public function indexAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('customercredit')->__('My Sharring List'));
        $this->renderLayout();
    }
    
    /**
     * Generate codes
     * @return url
     */
    public function generateAction() {
        $credit = (float)$this->getRequest()->getParam('credit_value');
        $qty    = 1;
        
        if(!$credit) {
             Mage::getSingleton('core/session')->addError($this->__("Credit value is required value."));
            return $this->_redirectReferer();
        }
        
        if($credit<MageWorx_CustomerCredit_Block_Customer_Refferals_Details::CC_MIN_CREDIT_CODE) {
            Mage::getSingleton('core/session')->addError($this->__("Please increase code value."));
            return $this->_redirectReferer();
        }
        if($credit>MageWorx_CustomerCredit_Block_Customer_Refferals_Details::CC_MAX_CREDIT_CODE) {
            Mage::getSingleton('core/session')->addError($this->__("Please decrease code value."));
            return $this->_redirectReferer();
        }
        
        if($qty>MageWorx_CustomerCredit_Block_Customer_Refferals_Details::CC_MAX_QTY) {
            Mage::getSingleton('core/session')->addNotice($this->__("Max code number is %s. Value was changed to maximum.",MageWorx_CustomerCredit_Block_Customer_Refferals_Details::CC_MAX_QTY));
            $qty = MageWorx_CustomerCredit_Block_Customer_Refferals_Details::CC_MAX_QTY;
        }
        
        $customerId = Mage::getModel('customer/session')->getCustomerId();
        $qty = $this->checkCustomerBalance($customerId,$credit,$qty);
        
        if($qty<1) {
            Mage::getSingleton('core/session')->addError($this->__("You can't create code with %s value. Your balance is %s", Mage::helper('core')->currency($credit), Mage::helper('core')->currency($this->_customerCredit)));
            return $this->_redirectReferer();
        }
        $data = array('qty'=>$qty,
            'code_length'       =>Mage::getStoreConfig('mageworx_customers/customercredit_recharge_codes/code_length'),
            'group_length'      =>Mage::getStoreConfig('mageworx_customers/customercredit_recharge_codes/group_length'),
            'group_separator'   =>Mage::getStoreConfig('mageworx_customers/customercredit_recharge_codes/group_separator'),
            'code_format'       =>Mage::getStoreConfig('mageworx_customers/customercredit_recharge_codes/code_format')
        );
        $codeModel = Mage::getModel('customercredit/code');
        $codeModel->setIsNew(true)
                ->setCredit($credit)
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->setIsActive(true)
                ->setOwnerId($customerId)
                ->setGenerate($data);
        try {
            $codeModel->generate();
            $lastItem = $codeModel->getCollection()->getLastItem();
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addException($e, $this->__('There was a problem with generate codes: %s', $e->getMessage()));
            return $this->_redirectReferer();
        }
        $this->decreaseCustomerCredit($customerId,$qty*$credit);
        Mage::getSingleton('core/session')->addSuccess($this->__('Credit Code %s was created.',$lastItem->getCode()));
        return $this->_redirectReferer();
    }
    
    /**
     * Check balance
     * @param int $customerId
     * @param flaot $credit
     * @param int $qty
     * @return int
     */
    public function checkCustomerBalance($customerId,$credit,$qty) {
        $customerCredit = Mage::getModel('customercredit/credit')->setCustomerId($customerId)->loadCredit()->getValue();
        $this->_customerCredit = $customerCredit;
        $codeCredit = $credit*$qty;
        if($customerCredit>=$codeCredit) {
            return $qty;
        } else {
            $qty = $customerCredit/$credit;
            $qty = (int)$qty;
            if($qty>0) {
                Mage::getSingleton('core/session')->addNotice($this->__('Number of codes was changed to %s.',$qty));
            }
            return $qty;
        }
    }
    
    /**
     * Decrease customer credit
     * @param int $customerId
     * @param float $credit
     * @return boolean
     */
    public function decreaseCustomerCredit($customerId,$credit) {
        $credit = 0-$credit;
        $creditLog = Mage::getModel('customercredit/credit_log');                   
        Mage::getModel('customercredit/credit')
            ->setCustomerId($customerId)
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
            ->setValueChange($credit)
            ->setActionType(MageWorx_CustomerCredit_Model_Credit_Log::ACTION_TYPE_CODE_CREATED)
            ->save();
        return true;
    }
}