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

class MageWorx_CustomerCredit_IndexController extends Mage_Core_Controller_Front_Action {

    /**
     * Action predispatch
     * Check customer authentication for some actions
     * @return MageWorx_CustomerCredit_IndexController
     */
    public function preDispatch() {
        parent::preDispatch();
        $action = $this->getRequest()->getActionName();
        $loginUrl = Mage::helper('customer')->getLoginUrl();

        if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return;
        }
        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
            return;
        }
        if (!Mage::helper('customercredit')->isShowCustomerCredit())
        {
            $this->norouteAction();
            return;
        }

        if (!Mage::helper('customercredit')->isEnabled()) {
            $this->norouteAction();
            return;
        }
        return $this;
    }

    public function indexAction() {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->loadLayoutUpdates();

        $data = Mage::getSingleton('customer/session')->getCustomercreditFormData(true);
        Mage::register('customercredit_code', new Varien_Object());
        if (!empty($data)) {
            Mage::registry('customercredit_code')->addData($data);
        }

        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('customercredit')->__('My Credit'));
        $this->renderLayout();
    }

    public function logAction() {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->loadLayoutUpdates();
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('customercredit')->__('My Credit Activity'));
        if ($block = $this->getLayout()->getBlock('customer.account.link.back')) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $this->renderLayout();
    }

    public function refillAction() {
        if (!Mage::helper('customercredit')->isEnabledCodes())
            $this->_forward('index');
        if ($this->getRequest()->has('customercredit_code')) {
            $code = $this->getRequest()->getPost('customercredit_code');
            try {
                $codeModel = Mage::getModel('customercredit/code')->loadByCode($code);
                $refillCredit = $codeModel->getCredit();
                $codeModel->useCode();

                Mage::getSingleton('customer/session')->addSuccess($this->__(
                                'Credit Balance was refilled with %s successfully using Recharge Code "%s".', Mage::helper('core')->currency($refillCredit), Mage::helper('core')->htmlEscape($codeModel->getCode()))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('customer/session')->setCustomercreditFormData($this->getRequest()->getPost())
                        ->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('customer/session')->addException($e, $this->__('Error occur while refilling the credit.'));
            }
            $this->_redirect('*');
        }
    }
    
    public function removeCodeAction() {
        $codeId = $this->getRequest()->getParam('code_id');
        $codeModel = Mage::getModel('customercredit/code')->load($codeId);
        $codeModel->setCustomerId($codeModel->getOwnerId());
        $code = $codeModel->getCode();
        $model = Mage::getModel('customercredit/credit');
        $model->refill($codeModel);
        
        try {
            $codeModel->delete();
            Mage::getSingleton('customer/session')->addSuccess($this->__('The code %s was successfully removed.',$code));
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('customer/session')->setCustomercreditFormData($this->getRequest()->getPost())
                    ->addError($e->getMessage());
        }
        return $this->_redirectReferer();
        
    }

    public function updateCreditPostAction() {
        Mage::getSingleton('checkout/session')->setUseInternalCredit(true);
        $this->_redirect('checkout/cart');
    }

    public function removeCreditUseAction() {
        Mage::getSingleton('checkout/session')->setUseInternalCredit(false);
        Mage::getSingleton('customer/session')->unsCustomCreditValue();
        $this->_redirect('checkout/cart');
    }
    
    public function makeLikeAction() {
        $currentUrl = $this->getRequest()->getParam('url');
        
        $customer = Mage::getModel('customer/session')->getCustomer();
        $customerId = $customer->getId();
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $customerGroupId = $customer->getGroupId();

        $store = Mage::app()->getStore();
        $websiteId = $store->getWebsiteId();
        
        $ruleModel = Mage::getResourceModel('customercredit/rules_collection');                                    
        $ruleModel->setValidationByCustomerGroup($customerGroupId);
        
        $model = Mage::getModel('customercredit/rules_customer_action');
        $collection = $model->getCollection();
        
        $actionTag = MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ACTION_FBLIKE;
        $log = Mage::getModel('customercredit/rules_customer_log');
        $logCollectionModel = $log->getCollection()->setActionTag($actionTag);
        
        foreach ($ruleModel->getData() as $rule) {   
            $customerActions = array();
            $conditions = unserialize($rule['conditions_serialized']);  
            foreach ($conditions['conditions'] as $key => $condition) {
                $coff = 1;
                $rest = 0;
                $skipUrl = false;
                if($actionTag) {
                    if ($condition['attribute']=='fb_like') {
                        $skipUrl = false;
                        $logCollection = $logCollectionModel->loadByRuleAndCustomer($rule['rule_id'], $customerId);
                        foreach ($logCollection as $item) {
                            if($item->getValue() == $currentUrl) {
                                $success[$key] = false;
                                $skipUrl = true;
                            }
                        }
                        
                        $action = $collection->loadByRuleAndCustomer($rule['rule_id'], $customerId)->getFirstItem();
                        if ($action->getId()) {
                            $currentValue = $action->getValue();
                        } else {
                            $currentValue = 0;
                        }
                        $nextValue = $currentValue+1;
                        if($nextValue >= $condition['value']) {
                           $coff = $nextValue / $condition['value'];
                           $coff = (int) $coff;
                           $rest = $nextValue - $condition['value']*$coff;
                           $success[$key] = true;
                        } else {
                            $rest = $nextValue;
                            $success[$key] = false;
                        }
                        $customerActions[] = array('rule'=>$rule,'rule_id'=>$rule['rule_id'],'customerId'=>$customerId,'actionTag'=>$actionTag,'rest'=>$rest,'coff'=>$coff,'nextValue'=>$nextValue); 
                    }
                }
            }

            $result = true;
            switch ($conditions['aggregator']){
                case 'any':
                    switch ($conditions['value']){
                        case '1':
                            if(!in_array(true, $success)){
                                    $result = false;
                            }
                            break;
                        case '0':
                            if (!in_array(false, $success)){
                                    $result = false;
                            }
                            break;
                    }
                    break;
                case 'all':
                    switch ($conditions['value']){
                        case 1:
                            if (in_array(false, $success)){
                                    $result = false;
                            }
                            break;
                        case 0:
                            if (in_array(true, $success)){
                                    $result = false;
                            }
                            break;
                    }
                    break;
            }
            if(count($customerActions)) {
                foreach ($customerActions as $actionValue) {
                    if(!$skipUrl) {
                        $log->setId(null)
                          ->setRuleId($actionValue['rule_id'])
                          ->setCustomerId($customerId)
                          ->setActionTag($actionTag)
                          ->setValue($currentUrl)
                          ->save();
                    }
                    $action->setRuleId($actionValue['rule_id'])
                        ->setCustomerId($actionValue['customerId'])
                        ->setActionTag($actionValue['actionTag']);
                    if($result) {
                        $action->setValue($actionValue['rest']);
                    } else {   
                        $action->setValue($actionValue['nextValue']);
                    }
                    $action->save();
             
                    if (!$result) continue;

                // if onetime
                if (isset($rule['is_onetime'])) $isOnetime = $rule['is_onetime']; else $isOnetime = 1;
                $rulesCustomer = Mage::getModel('customercredit/rules_customer')->loadByRuleAndCustomer($rule['rule_id'], $customerId);

                if (!$rulesCustomer || !$rulesCustomer->getId()) {
                    $rulesCustomer = Mage::getModel('customercredit/rules_customer')->setRuleId($rule['rule_id'])->setCustomerId($customerId)->save();                    
                } else {
                    if ($isOnetime) continue;
                }
                $creditLog = Mage::getModel('customercredit/credit_log');                   
                Mage::getModel('customercredit/credit')
                        ->setCustomerId($customerId)
                        ->setWebsiteId($websiteId)
                        ->setRuleName($actionValue['rule']['name'])
                        ->setRulesCustomerId($rulesCustomer->getId())                            
                        ->setValueChange($actionValue['rule']['credit']*$coff)
                        ->setActionType(MageWorx_CustomerCredit_Model_Credit_Log::ACTION_TYPE_CREDIT_ACTION)
                        ->save();
                }
            }
        }
        return $this;
    }
    
    /**
     * Change patyment credit
     */
    private function _changeCredit() {
        $creditValue    = $this->getRequest()->getParam('custmer_credit_value');
        $credit         = $creditValue/Mage::getStoreConfig('mageworx_customers/customercredit_credit/exchange_rate');
        $realCreditValue= Mage::helper('customercredit')->getRealCreditValue();
        
        $shippingAddress= Mage::getModel('checkout/cart')->getQuote()->getShippingAddress();
        $shipping       = floatval($shippingAddress->getShippingAmount() - $shippingAddress->getShippingTaxAmount());
        $subtotal       = floatval(Mage::getModel('checkout/cart')->getQuote()->getSubtotalWithDiscount());
        $tax            = floatval($shippingAddress->getTaxAmount());    
        $total          = $subtotal+$shipping+$tax;
        
        $creditTotals = Mage::helper('customercredit')->getCreditTotals();
        if (count($creditTotals)<3) {
            $creditLeft = 0;
            foreach ($creditTotals as $field) {
                switch ($field) {
                    case 'subtotal':                            
                        $creditLeft += $subtotal;
                        break;
                    case 'shipping':
                        $creditLeft += $shipping;                   
                        break;
                    case 'tax':
                        $creditLeft += $tax;
                        break;    
                    case 'fees':
                        $baseCreditLeft += $shippingAddress->getBaseMultifeesAmount();
                        $creditLeft += $shippingAddress->getMultifeesAmount();
                        break;  
                }
            }
        } else {
            $creditLeft = $total;
        }
        if($credit>$creditLeft) {
            $credit = $creditLeft;
        }
        
        $creditValue = $credit*Mage::getStoreConfig('mageworx_customers/customercredit_credit/exchange_rate');
        if($creditValue>$realCreditValue) {
            $creditValue = $realCreditValue;
        }
        Mage::getSingleton('customer/session')->setCustomCreditValue($creditValue);
        return true;
    }
    
    public function reload_paymentAction()
    {
        $this->_changeCredit();
        $session = Mage::getSingleton('checkout/session');
        $session->setUseInternalCredit(true);
        Mage::getModel('checkout/cart')->getQuote()->collectTotals();
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function reload_payment_ajaxAction()
    {
        $this->_changeCredit();
        $session = Mage::getSingleton('checkout/session');
        $session->setUseInternalCredit(true);
        Mage::getModel('checkout/cart')->getQuote()->collectTotals();
    }
    
    public function reloadCartAction() {
        $this->_changeCredit();
        return $this->_redirect('checkout/cart/');
    }
}