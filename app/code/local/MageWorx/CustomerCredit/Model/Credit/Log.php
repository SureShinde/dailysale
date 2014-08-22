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
 
class MageWorx_CustomerCredit_Model_Credit_Log extends Mage_Core_Model_Abstract
{
    const ACTION_TYPE_UPDATED                   = 0;
    const ACTION_TYPE_USED                      = 1;
    const ACTION_TYPE_REFUNDED                  = 2;
    const ACTION_TYPE_CREDITRULE                = 3;
    const ACTION_TYPE_CANCELED                  = 4;
    const ACTION_TYPE_CREDIT_PRODUCT            = 5;
    const ACTION_TYPE_CREDIT_ACTION             = 6;
    const ACTION_TYPE_CODE_CREATED              = 7;
    const ACTION_TYPE_IMPORT                    = 8;
    const ACTION_TYPE_EXPIRED                   = 9;
    const ACTION_TYPE_API                       = 10;
    const ACTION_TYPE_ORDER_EDIT                = 11;
    const ACTION_TYPE_ORDER_CANCEL_AFTER_EDIT   = 12;
    const ACTION_TYPE_SYNC                      = 14;
    
    protected function _construct() {
        $this->_init('customercredit/credit_log');
    }
    
    public function getActionTypesOptions() {
        return array(
            self::ACTION_TYPE_UPDATED                   => Mage::helper('customercredit')->__('Modified'),
            self::ACTION_TYPE_USED                      => Mage::helper('customercredit')->__('Used'),
            self::ACTION_TYPE_REFUNDED                  => Mage::helper('customercredit')->__('Refunded'),
            self::ACTION_TYPE_CREDITRULE                => Mage::helper('customercredit')->__('Modified'),
            self::ACTION_TYPE_CANCELED                  => Mage::helper('customercredit')->__('Canceled'),
            self::ACTION_TYPE_CREDIT_PRODUCT            => Mage::helper('customercredit')->__('Modified by Credit Product'),
            self::ACTION_TYPE_CREDIT_ACTION             => Mage::helper('customercredit')->__('Added'),
            self::ACTION_TYPE_CODE_CREATED              => Mage::helper('customercredit')->__('Decreased'),
            self::ACTION_TYPE_IMPORT                    => Mage::helper('customercredit')->__('Imported'),
            self::ACTION_TYPE_EXPIRED                   => Mage::helper('customercredit')->__('Expired'),
            self::ACTION_TYPE_API                       => Mage::helper('customercredit')->__('API'),
            self::ACTION_TYPE_ORDER_EDIT                => Mage::helper('customercredit')->__('Edit Order'),
            self::ACTION_TYPE_ORDER_CANCEL_AFTER_EDIT   => Mage::helper('customercredit')->__('Edit Order by Extended Orders'),
            self::ACTION_TYPE_SYNC                      => Mage::helper('customercredit')->__('Sync Balance'),
        );
    }
    
    protected function _beforeSave() {
        if (!$this->hasCreditModel() || !$this->getCreditModel()->getId()) Mage::throwException(Mage::helper('customercredit')->__('Customer credit hasn\'t assigned.'));
        
        $this->setCreditId($this->getCreditModel()->getId());
        $this->setComment($this->_getComment());
        return parent::_beforeSave();
    }
    
    public function save() {
        if(!$this->getValueChange()) {
            return ;
        }
        if($this->getActionType()==self::ACTION_TYPE_ORDER_EDIT) {
            return;
        }
        // if send email
        if (Mage::helper('customercredit')->isSendNotificationBalanceChanged()) {          
            $customerId = $this->getCreditModel()->getCustomerId();
            $customer = Mage::getModel('customer/customer')->load($customerId);
            $data = array('value_change'=>$this->getValueChange(),'credit_value'=>$this->getValue(),"comment"=>str_replace(array('<span class="price">','</span>'),'',$this->_getComment()));
            $customer->setData('customer_credit_data',$data);
            Mage::helper('customercredit')->sendNotificationBalanceChangedEmail($customer);
        }
        return parent::save();
    }
    
    protected function _getComment() {
        $comment = '';
        switch ($this->getActionType()) {
            case self::ACTION_TYPE_UPDATED :
                if ($this->getCreditModel()->hasRechargeCode()) {
                    if(Mage::app()->getRequest()->getActionName() == 'removeCode') {
                        $code = Mage::getModel("customercredit/code")->load(Mage::app()->getRequest()->getParam('code_id'));
                        $comment =  Mage::helper('customercredit')->__('Credit Code %s was removed.', $code->getCode());
                    } else {
                        $comment =  Mage::helper('customercredit')->__('By Recharge Code %s', $this->getCreditModel()->getRechargeCode());
                    }
                } elseif ($user = Mage::getSingleton('admin/session')->getUser()) {                    
                    if ($this->getCreditModel()->getComment()) {
                        $comment =  $this->getCreditModel()->getComment();
                    }
                }
                break;
            case self::ACTION_TYPE_ORDER_EDIT :
            case self::ACTION_TYPE_ORDER_CANCEL_AFTER_EDIT :
                    $comment = Mage::helper('customercredit')->__('Order #%s was edited. Credits was changed. %s',$this->getCreditModel()->getOrder()->getIncrementId(),$this->getComment());
                break;    
            case self::ACTION_TYPE_USED :
                $this->_checkOrder();
                $comment =  Mage::helper('customercredit')->__('In Order #%s', $this->getCreditModel()->getOrder()->getIncrementId());
                break;
            case self::ACTION_TYPE_REFUNDED :
                $this->_checkCreditmemo();
                if ($this->getCreditModel()->getCreditRule()) {                
                    $comment =  Mage::helper('customercredit')->__("Credit Rule(s) Order #%s; \nCredit Memo #%s", $this->getCreditModel()->getOrder()->getIncrementId(), $this->getCreditModel()->getCreditmemo()->getIncrementId());
                    $this->getCreditModel()->setCreditRule(null);
                } else {
                    $comment =  Mage::helper('customercredit')->__("Order #%s; \nCredit Memo #%s", $this->getCreditModel()->getOrder()->getIncrementId(), $this->getCreditModel()->getCreditmemo()->getIncrementId());
                }    
                break;
            case self::ACTION_TYPE_CANCELED :
                if ($this->getCreditModel()->getCreditRule()) {
                    $comment =  Mage::helper('customercredit')->__("Credit Rule(s) In Order #%s", $this->getCreditModel()->getOrder()->getIncrementId());
                    $this->getCreditModel()->setCreditRule(null);
                } else {
                    $comment =  Mage::helper('customercredit')->__("Order #%s", $this->getCreditModel()->getOrder()->getIncrementId());
                }    
                break;
            case self::ACTION_TYPE_CREDITRULE :
                $orderIncrementId = $this->getCreditModel()->getOrder()->getIncrementId();
            	if ($orderIncrementId>0) {                    
                    $comment = Mage::helper('customercredit')->__('Credit Rule "%s" In Order #%s', $this->getCreditModel()->getRuleName(), $orderIncrementId);
                } else {
                    $comment = Mage::helper('customercredit')->__('Credit Rule');
                }    
            	break;
            case self::ACTION_TYPE_CREDIT_PRODUCT :
                $orderIncrementId = $this->getCreditModel()->getOrder()->getIncrementId();
            	if ($orderIncrementId>0) {                    
                    $comment = Mage::helper('customercredit')->__('Purchase of Credit Units in Order #%s', $orderIncrementId);
                } else {
                    $comment = Mage::helper('customercredit')->__('Purchase of Credit Units');
                }    
            	break;    
            case self::ACTION_TYPE_CREDIT_ACTION :
                    $comment = Mage::helper('customercredit')->__('Customer completed rule "%s" action.',$this->getCreditModel()->getRuleName());
                break;    
            case self::ACTION_TYPE_CODE_CREATED :
                    $lastItem = Mage::getModel('customercredit/code')->getCollection()->getLastItem();
                    $comment = Mage::helper('customercredit')->__('Credit Code %s was created.',$lastItem->getCode());
                break;    
            case self::ACTION_TYPE_IMPORT :
                    $comment = Mage::helper('customercredit')->__('%s',$this->getComment());
                break;    
            case self::ACTION_TYPE_EXPIRED :
                    $comment = Mage::helper('customercredit')->__('Credits was expired. %s',$this->getComment());
                break;    
            case self::ACTION_TYPE_API :
                    $comment = Mage::helper('customercredit')->__('Credits was changed. %s',$this->getComment());
                break;    
            case self::ACTION_TYPE_SYNC :
                    $comment = Mage::helper('customercredit')->__('Sync Store Credits Balances. %s',$this->getComment());
                break;    
            default :
                Mage::throwException(Mage::helper('customercredit')->__('Unknown log action type.'));
                break;
        }
        if(Mage::registry('customer_credit_order_place_amount_value')) {
            $comment .=" (".Mage::helper('core')->currency(Mage::registry('customer_credit_order_place_amount_value')).", ".Mage::helper('core')->__('Exchange rate is %s',Mage::getStoreConfig('mageworx_customers/customercredit_credit/exchange_rate')).")";
        }
        return $comment;
    }
    
    protected function _checkCreditmemo() {
        if (!$this->getCreditModel()->getCreditmemo() || !$this->getCreditModel()->getCreditmemo()->getIncrementId()) {
            Mage::throwException(Mage::helper('customercredit')->__('Creditmemo not set.'));
        }
        $this->_checkOrder();
    }
    
    protected function _checkOrder() {
        if (!$this->getCreditModel()->getOrder() || !$this->getCreditModel()->getOrder()->getIncrementId()) {
            Mage::throwException(Mage::helper('customercredit')->__('Order not set.'));
        }
    }
    
    public function loadByOrderAndAction($orderId, $actionType, $rulesCustomerId = false) {
	$this->getResource()->loadByOrderAndAction($this, $orderId, $actionType, $rulesCustomerId);
        return $this;
    }
}