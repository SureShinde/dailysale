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

class MageWorx_CustomerCredit_Model_Observer
{
    
    private $_customer;
    private $_order;
    private $_object;
    private $_ruleQty;
    private $_creditValue = null;

    
    /**
     * Add credits to order in magento after 1.9
     * @param Varien_Event_Observer $observer
     */
    public function saveCreditsInOrder($observer) {
        $quote = $observer->getQuote();
        if (version_compare(Mage::getVersion(), '1.9', '<')) return ;
        foreach ($quote->getAllAddresses() as $address) {
            $baseCredit = $address->getBaseCustomerCreditAmount();
            $credit     = $address->getCustomerCreditAmount();
            $quote->setBaseCustomerCreditAmount($quote->getBaseCustomerCreditAmount() + $baseCredit);
            $quote->setCustomerCreditAmount($quote->getCustomerCreditAmount() + $credit);
        }
    }
    
    /**
     * Save Customer Credit Code After
     * @param Varien_Event_Observer $observer
     */
    public function saveCodeAfter(Varien_Event_Observer $observer) {
        $code = $observer->getEvent()->getCode();
        $code->getLogModel()
            ->setCodeModel($code)
            ->save();
    }

    /**
     * Save Customer Credit Value After
     * @param Varien_Event_Observer $observer
     */
    public function saveCreditAfter(Varien_Event_Observer $observer) {
        $credit = $observer->getEvent()->getCredit();
        $credit->getLogModel()
            ->setCreditModel($credit)
            ->save();
    }

    /**
     * Prepare Customer Credit Value when Customer Save
     * @param Varien_Event_Observer $observer
     */
    public function prepareCustomerSave(Varien_Event_Observer $observer) {
        $customer = $observer->getEvent()->getCustomer();
        $request  = $observer->getEvent()->getRequest();
        if ($data = $request->getPost('customercredit'))
        {
            $customer->setCustomerCreditData($data);
        }
    }

    /**
     * Save Customer Credit Value when Customer Save
     * @param Varien_Event_Observer $observer
     */
    public function saveCustomerAfter(Varien_Event_Observer $observer) {
        if (!Mage::helper('customercredit')->isEnabled()) return false;                
        $customer = $observer->getEvent()->getCustomer();
        $customerCredit = Mage::getModel('customercredit/credit');
//        echo "<pre>"; print_r($customer->getData()); exit;
        if (($data = $customer->getCustomerCreditData()) && !empty($data['value_change'])) {
            // no minus
            if ((floatval($data['credit_value']) + floatval($data['value_change'])) < 0 ) $data['value_change'] = floatval($data['credit_value'])*-1;
          
            # Depricated since 2.5.4
            /**
            //if send email
            if (Mage::helper('customercredit')->isSendNotificationBalanceChanged()) {                
                Mage::helper('customercredit')->sendNotificationBalanceChangedEmail($customer);
            }
            */
        }
          $customerCredit->setData($data)->setCustomer($customer)->save();

    }
    
    /**
     * Collect totals before
     * @param Varien_Event_Observer $observer
     */
    public function collectQuoteTotalsBefore(Varien_Event_Observer $observer) {
        $quote = $observer->getEvent()->getQuote();
        $quote->setCustomerCreditTotalsCollected(false);
    }

    /**
     * Place order Before
     * @param Varien_Event_Observer $observer
     */
    public function placeOrderBefore(Varien_Event_Observer $observer) {
        
        if (!Mage::helper('customercredit')->isEnabled()) return;

        $order = $observer->getEvent()->getOrder();
        /* @var $order Mage_Sales_Model_Order */
        if ($order->getBaseCustomerCreditAmount() > 0) {
            
            $credit = Mage::helper('customercredit')->getCreditValue($order->getCustomerId(), Mage::app()->getStore($order->getStoreId())->getWebsiteId());            
            $credit = $credit/Mage::getStoreConfig('mageworx_customers/customercredit_credit/exchange_rate');
            if (($order->getBaseCustomerCreditAmount() - $credit) >= 0.0001) {
                Mage::getSingleton('checkout/type_onepage')
                    ->getCheckout()
                    ->setUpdateSection('payment-method')
                    ->setGotoSection('payment');
                Mage::throwException(Mage::helper('customercredit')->__('Not enough Credit Amount to complete this Order.'));
            }
        }
    }
    
    /**
     * Check can using customer credit when order place
     * @param Varien_Event_Observer $observer
     * @return boolean
     */
    public function reduceCustomerCreditValue(Varien_Event_Observer $observer) {
        if (!Mage::helper('customercredit')->isEnabled()) return false;
        $order = $observer->getEvent()->getOrder();
        $needUseCreditMarker = Mage::registry('need_reduce_customercredit');
        /* @var $order Mage_Sales_Model_Order */
        if (($order->getBaseCustomerCreditAmount()>0) || $needUseCreditMarker) {
            //reduce credit value
            Mage::getModel('customercredit/credit')->useCredit($order);
            return true;            
        }
        return false;
    }

    /**
     * Recalc Invoice
     * @param Varien_Event_Observer $observer
     */
    public function saveInvoiceAfter(Varien_Event_Observer $observer) {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();

        if ($invoice->getBaseCustomerCreditAmount()) {
            $order->setBaseCustomerCreditInvoiced($order->getBaseCustomerCreditInvoiced() + $invoice->getBaseCustomerCreditAmount());
            $order->setCustomerCreditInvoiced($order->getCustomerCreditInvoiced() + $invoice->getCustomerCreditAmount());
        }
    }

    /**
     * Check order status
     * @param Varien_Event_Observer $observer
     * @return MageWorx_CustomerCredit_Model_Observer
     */
    public function loadOrderAfter(Varien_Event_Observer $observer) {
        $order = $observer->getEvent()->getOrder();

        if ($order->canUnhold()) {
            return $this;
        }

        if ($order->getState() === Mage_Sales_Model_Order::STATE_CANCELED ||
            $order->getState() === Mage_Sales_Model_Order::STATE_CLOSED ) {
            return $this;
        }


        if (abs($order->getCustomerCreditInvoiced() - $order->getCustomerCreditRefunded())<.0001) {
            return $this;
        }
        $order->setForcedCanCreditmemo(true);

        return $this;
    }
    
    /**
     * Check if need recalc refund
     * @param Varien_Event_Observer $observer
     */
    public function returnRefandData($observer) {
        $order = $observer->getOrder();
        if($value = Mage::registry('need_setnull_total_refunded')) {
            Mage::unregister('need_setnull_total_refunded');
            $order->setTotalRefunded($value)->save();
        }
    }

    /**
     * Create creditmemo
     * @param Varien_Event_Observer $observer
     * @return MageWorx_CustomerCredit_Model_Observer
     */
    public function refundCreditmemo(Varien_Event_Observer $observer) {                
        
        $creditmemo = $observer->getEvent()->getCreditmemo();
        if(Mage::registry('cc_order_refund')) {
            return true;
        }
        Mage::register('cc_order_refund', true, true);
        $order = $creditmemo->getOrder();        

        // get real total
        $baseTotal = $creditmemo->getBaseGrandTotal();
        if ($order->getBaseCustomerCreditAmount()>$order->getBaseCustomerCreditRefunded()) {
            $baseTotal += $creditmemo->getBaseCustomerCreditAmount();
        }
        $baseTotal = floatval($baseTotal);
        
        // add message Returned credit amount..
        $post = Mage::app()->getRequest()->getParam('creditmemo');                        
        if (isset($post['credit_return'])) {
            $baseCreditAmountReturn = floatval($post['credit_return']);
            // validation
            if ($baseCreditAmountReturn>$baseTotal) {
                $baseCreditAmountReturn = $baseTotal;
            }
        } else {
            $baseCreditAmountReturn = $creditmemo->getBaseCustomerCreditAmount();
        }
        
        if ($baseCreditAmountReturn>0) {
            // set CustomerCreditRefunded
            $order->setBaseCustomerCreditRefunded($order->getBaseCustomerCreditRefunded() + $baseCreditAmountReturn);            
            $creditAmountReturn = $creditmemo->getStore()->convertPrice($baseCreditAmountReturn, false, false);
            $order->setCustomerCreditRefunded($order->getCustomerCreditRefunded() + $creditAmountReturn);                                  
            
            // if payment is not 100% credit
            if ($order->getBaseGrandTotal()!=0) {
                // set [base_]total_refunded 
                $order->setBaseTotalRefunded(($order->getBaseTotalRefunded() - $creditmemo->getBaseGrandTotal()) + ($baseTotal - $baseCreditAmountReturn));
                $total = $creditmemo->getStore()->convertPrice($baseTotal, false, false);

                $tmpTotalRefunded = ($order->getTotalRefunded() - $creditmemo->getGrandTotal()) + ($total - $creditAmountReturn);
                $b = $order->getTotalRefunded();
                if($order->getTotalRefunded()!=$tmpTotalRefunded) {
                    $order->setTotalRefunded($tmpTotalRefunded);
                    $a = $order->getTotalRefunded();
                    if($order->getTotalRefunded()+$creditAmountReturn-$total < .0001) {
                        Mage::register('need_setnull_total_refunded',$order->getTotalRefunded(),TRUE);
                        $order->setTotalRefunded($order->getTotalPaid());
                    }
                }
                
                
            }
            
            if (abs($order->getCustomerCreditInvoiced() - $order->getCustomerCreditRefunded())<.0001) {
                $order->unsForcedCanCreditmemo();
                 Mage::register('can_closed_order', true, true);
            }
            
           
            // set message
            $payment = $order->getPayment();
            

            if ($order->getBaseGrandTotal()!=0) {
                if ($creditmemo->getDoTransaction() && $creditmemo->getInvoice()) {
                    // online
                    $message = Mage::helper('sales')->__('Refunded amount of %s online.', $payment->getOrder()->getBaseCurrency()->formatTxt($baseTotal - $baseCreditAmountReturn))."<br/>";
                } else {
                    // offline
                    $message = Mage::helper('sales')->__('Refunded amount of %s offline.', $payment->getOrder()->getBaseCurrency()->formatTxt($baseTotal - $baseCreditAmountReturn))."<br/>";
                }
            } else {
                $message = '';
            }
            $message .= Mage::helper('customercredit')->__('Returned credit amount: %s.', $payment->getOrder()->getBaseCurrency()->formatTxt($baseCreditAmountReturn));
            $historyRefund = $payment->getOrder()->getStatusHistoryCollection()->getLastItem();
            $historyRefund->setComment($message);
        }
        Mage::register('credit_need_refund',TRUE);
        return $this;
    }

    /**
     * Add credits to PayPal Cart
     * @param Varien_Event_Observer $observer
     * @return MageWorx_CustomerCredit_Model_Observer
     */
    public function paypalCart($observer) {
        $model = $observer->getEvent()->getPaypalCart();

        if (Mage::app()->getStore()->isAdmin()) {
            $allItems = Mage::getSingleton('adminhtml/sales_order_create')->getQuote()->getAllItems();
            $productIds = array();
            foreach ($allItems as $item) {
                $productIds[] = $item->getProductId();
            }
        } else {
            $productIds = Mage::getSingleton('checkout/cart')->getProductIds();            
        }

        if (count($productIds)==0) return $this;
        
        
        $address = $model->getSalesEntity()->getIsVirtual() ? $model->getSalesEntity()->getBillingAddress() : $model->getSalesEntity()->getShippingAddress();

        $credit = $address->getCustomerCreditAmount(); 
        if($credit == NULL)
        {
            $credit = 0;
            $credit = $model->getSalesEntity()->getCustomerCreditAmount();
        }
         $model->updateTotal(Mage_Paypal_Model_Cart::TOTAL_DISCOUNT,$credit);
    }
    
    # Paypal method for f... GoMage Checkout
    /**
    public function paypalCart($observer)
    {
        $model = $observer->getEvent()->getPaypalCart();

        if (Mage::app()->getStore()->isAdmin()) {
            $allItems = Mage::getSingleton('adminhtml/sales_order_create')->getQuote()->getAllItems();
            $productIds = array();
            foreach ($allItems as $item) {
                $productIds[] = $item->getProductId();
            }
        } else {
            $quoteId = $model->getSalesEntity()->getQuoteId();
            $quote = Mage::getSingleton('gomage_checkout/type_onestep')->getQuote();
            $quote = $quote->load($quoteId);
            foreach ($quote->getAllVisibleItems() as $item)
            {
                $productIds[] = $item->getProduct()->getId();
            }
        }

        if (count($productIds)==0) return $this;

        $address = $model->getSalesEntity()->getBillingAddress();
        foreach ($productIds as $productId) {
            $product = Mage::getModel('catalog/product')->load($productId);
            $productTypeId = $product->getTypeId();
            if ($productTypeId!='downloadable' && !$product->isVirtual()) {
                $address = $model->getSalesEntity()->getShippingAddress();
                break;
            }
        }
        
        $credit = $address->getCustomerCreditAmount(); 
        if($credit == NULL)
        {
            $credit = 0;
            $credit = $model->getSalesEntity()->getCustomerCreditAmount();
        }
         $model->updateTotal(Mage_Paypal_Model_Cart::TOTAL_DISCOUNT,$credit);
    }
    */

    /**
     * Check creditmemo
     * @param Varien_Event_Observer $observer
     * @return MageWorx_CustomerCredit_Model_Observer
     */
    public function saveCreditmemoAfter(Varien_Event_Observer $observer) {
        if(Mage::registry('credit_need_refund')) {
            Mage::getModel('customercredit/credit')->refund($observer->getEvent()->getCreditmemo(), Mage::app()->getRequest()->getParam('creditmemo'));        
        }
        return $this;
    }
    
    /**
     * Exec Rule
     * @param MageWorx_CustomerCredit_Model_Rules $rule
     * @return boolean
     */
    private function _executeRule($rule) {
        if ($this->_checkConditions($rule)) {
            $rule = $this->_calculateCredit($rule);
            $this->_sendLog($rule);
        }
        return true;
    }
    
    /**
     * Check rule conditions 
     * @param MageWorx_CustomerCredit_Model_Rules $rule
     * @return boolean
     */
    private function _checkConditions($rule) {
        $conditions = unserialize($rule['conditions_serialized']);                
        $customerActions = array();
        $actionTag = MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ORDER_COMPLETE;
        $model = Mage::getModel('customercredit/rules_customer_action');
        $collection = $model->getCollection();
        if(isset($conditions) && isset($conditions['conditions'])) {
            foreach ($conditions['conditions'] as $key => $condition) {
                $log = Mage::getModel('customercredit/rules_customer_log');
                $success[$key] = true;
                $skipUrl = false;
                switch ($condition['attribute']) {
                    case 'registration':
                        if(!$this->_customer) return false;
                        $actionTag = MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ACTION_REGISTRATION;
                        $logCollectionModel = $log->getCollection()->setActionTag($actionTag);
                        $logCollection = $logCollectionModel->loadByRuleAndCustomer($rule['rule_id'], $this->_customer->getId());
                        $createdAt = $this->_customer->getCreatedAt(0);
                        $createdAt = str_replace('T',' ',$createdAt);
                        $regArr = explode(' ', $createdAt, 2);
                        $regDate = explode('-', $regArr[0], 3);
                        $regTimestamp = mktime(0, 0, 0, $regDate[1], $regDate[2], $regDate[0]);

                        $ruleRegDate = explode('-', $condition['value'], 3);
                        $ruleRegTimestamp = mktime(0, 0, 0, $ruleRegDate[1], $ruleRegDate[2], $ruleRegDate[0]);

                        if (!version_compare($regTimestamp, $ruleRegTimestamp, $condition['operator'])){
                            $success[$key] = false;
                        }
                        if($logCollection->getSize()) {
                                $success[$key] = false;
                                $skipUrl = true;
                                break;
                        }
                        break;
                    case 'first_order':
                        if(!$this->_order) return false;
                        $actionTag = MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ACTION_PLACEORDER;
                        $logCollectionModel = $log->getCollection()->setActionTag($actionTag);
                        $orders = Mage::getResourceModel('sales/order_collection');
                        $orders->getSelect()->where('customer_id=?',$this->_customer->getId());
                        $orders->load();

                        $items = $orders->getItems();
                        $collectionSize = count($items);
                        if ($collectionSize > 1){
                            $success[$key] = false;
                        } else {
                            $success[$key] = true;
                        }
                        break;
                    case 'total_amount':
                        if(!$this->_order) return false;
                        $actionTag = MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ACTION_PLACEORDER;
                        $logCollectionModel = $log->getCollection()->setActionTag($actionTag);
                        $orders = Mage::getResourceModel('sales/order_collection');
                        $orders->getSelect()
                            ->reset(Zend_Db_Select::WHERE)
                            ->columns(array('grand_subtotal' => 'SUM(subtotal)'))
                            ->where('customer_id='.$this->_customer->getId())
                            ->group('customer_id');
                        $data = $orders->getData();
                        #Depricated 2.6.0
                        /**
                        if (count($data) != 1){
                            $success[$key] = false;
                        }
                        */
                        if (!version_compare($data[0]['grand_subtotal'], $condition['value'], $condition['operator'])){
                            $success[$key] = false;
                        }
                        break;
                    case 'place_order':
                        if(!$this->_order) return false;
                        $actionTag = MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ACTION_PLACEORDER;
                        $logCollectionModel = $log->getCollection()->setActionTag($actionTag);
                        if (isset($rule['is_onetime'])) $isOnetime = $rule['is_onetime']; else $isOnetime = 1;
                        if($condition['value']==1) {
                            $logCollection = $logCollectionModel->loadByRuleAndCustomer($rule['rule_id'], $this->_customer->getId());
                            $logCollection->getSelect()->order('id ASC');
                            if($logCollection->getSize() && $isOnetime) {
                                $lastItem = $logCollection->getLastItem();
                                $success[$key] = false;
                                $skipUrl = true;
                                break;
                            }
                            $success[$key] = true;
                        }
                        break;
                    case 'newsletter_signup':
                        if(!$this->_customer) return false;
                        $actionTag = MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ACTION_SUBSCRIBE;
                        $logCollectionModel = $log->getCollection()->setActionTag($actionTag);
                        $logCollection = $logCollectionModel->loadByRuleAndCustomer($rule['rule_id'], $this->_customer->getId());
                        if($logCollection->getSize()) {
                                $success[$key] = false;
                                $skipUrl = true;
                                break;
                        }
                        $success[$key] = true;
                        break;
                    case 'tag_product':
                        if(!$this->_object) return false;
                        $coff = 1;
                        $rest = 0;
                        $skipUrl = false;
                        $actionTag = MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ACTION_TAG;
                        $logCollectionModel = $log->getCollection()->setActionTag($actionTag);
                        $logCollection = $logCollectionModel->loadByRuleAndCustomer($rule['rule_id'], $this->_customer->getId());
                        foreach ($logCollection as $item)
                        {
                            if($item->getValue() == $this->_object->getTagId()) {
    //                            $success[$key] = false;
                                $skipUrl = true;
                            }
                        }
                        $action = $collection->loadByRuleAndCustomer($rule['rule_id'], $this->_customer->getId())->getFirstItem();
                        if ($action->getId()) {
                            $currentValue = $action->getValue();
                        } else {
                            $currentValue = 0;
                        }
                        $nextValue = $currentValue+1;
                        if($nextValue >= $condition['value'])
                        {
                           $coff = $nextValue / $condition['value'];
                           $coff = (int) $coff;
                           $rest = $nextValue - $condition['value']*$coff;
    //                       $success[$key] = true;
                        }
                        else {
                            $rest = $nextValue;
    //                        $success[$key] = false;
                        }
                        $success[$key] = true;
                        $customerActions[] = array('rule'=>$rule,'rule_id'=>$rule['rule_id'],'customerId'=>$this->_customer->getId(),'actionTag'=>$actionTag,'rest'=>$rest,'coff'=>$coff,'nextValue'=>$nextValue); 
                        break;
                    case 'review_product':
                        if(!$this->_object) return false;
                        if(!$this->_customer) return false;
                        $customerId = $this->_customer->getId();
                        $store = Mage::app()->getStore($this->_object->getStoreId());
                        $websiteId = $store->getWebsiteId();
                        $log->setWebsiteId($websiteId);
                        $customerActions = array();
                        $coff = 1;
                        $rest = 0;
                        $skipUrl = false;
                        $actionTag = MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ACTION_REVIEW;
                        $logCollectionModel = $log->getCollection()->setActionTag($actionTag);
                        $logCollection = $logCollectionModel->loadByRuleAndCustomer($rule['rule_id'], $this->_customer->getId());
                        foreach ($logCollection as $item)
                        {
                            if($item->getValue() == $this->_object->getReviewId()) {
                                $success[$key] = false;
                                $skipUrl = true;
                            }
                        }


                        $action = $collection->loadByRuleAndCustomer($rule['rule_id'], $this->_customer->getId())->getFirstItem();
                        if ($action->getId()) {
                            $currentValue = $action->getValue();
                        } else {
                            $currentValue = 0;
                        }
                        $nextValue = $currentValue+1;
                        if($nextValue >= $condition['value'])
                        {
                           $coff = $nextValue / $condition['value'];
                           $coff = (int) $coff;
                           $rest = $nextValue - $condition['value']*$coff;
                           $success[$key] = true;
                        }
                        else {
                            $rest = $nextValue;
                            $success[$key] = false;
                        }
                        $customerActions[] = array('rule'=>$rule,'rule_id'=>$rule['rule_id'],'customerId'=>$customerId,'actionTag'=>$actionTag,'rest'=>$rest,'coff'=>$coff,'nextValue'=>$nextValue); 

                        break;
                    case 'birthday':
                        $actionTag = MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ACTION_DOB;
                        $logCollectionModel = $log->getCollection()->setActionTag($actionTag);
                        $logCollection = $logCollectionModel->loadByRuleAndCustomer($rule['rule_id'], $this->_customer->getId());
                        $logCollection->getSelect()->order('id ASC');
                        if($logCollection->getSize()) {
                            $lastItem = $logCollection->getLastItem();
                            if(time() - $lastItem->getValue() < 31104000) { // one year
                                $success[$key] = false;
                                $skipUrl = true;
                                break;
                            }
                        }
                        $success[$key] = true;
                        break;
                    default :
                        // product atributes:
                        $success[$key] = false;  
                        $actionTag = MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ORDER_COMPLETE;
                        if($this->_order && ($this->_order->getStatus()=='complete')) {
                            $products = $this->_order->getAllItems();
                            $store = Mage::app()->getStore($this->_order->getStoreId());
                            $websiteId = $store->getWebsiteId();
                            $log->setWebsiteId($websiteId);
                            $this->_customer = Mage::getModel('customer/customer')->load($this->_order->getCustomerId());
                            $conditionProductModel = Mage::getModel($condition['type'])->loadArray($condition);   
                            $conditionProductModel->getValueParsed();
                            $quoteId = $this->_order->getQuoteId();
                            $quote = Mage::getModel('sales/quote')->load($quoteId);

                            foreach($products as $item) {
                                $product = Mage::getModel('catalog/product')->load($item->getProductId());
                                $product->setQuote($quote);
                                if ($conditionProductModel->validate($product)) {
                                    $success[$key] = true;
                                    $this->_ruleQty += $item->getQtyOrdered() - $item->getQtyRefunded() - $item->getQtyCanceled();
                                //    break;
                                }                                    
                            }  
                        }
                }

                $result = $this->_checkAggregator($conditions,$success);
                if($condition['attribute']!=='review_product') {
                    if(!$result) return false;
                }
                if(count($customerActions)) {
                    if($actionTag == MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ACTION_TAG) {
                        foreach ($customerActions as $actionValue) {
                            if(!$skipUrl) {
                                $log->setId(null)
                                  ->setRuleId($actionValue['rule_id'])
                                  ->setCustomerId($this->_customer->getId())
                                  ->setActionTag($actionTag)
                                  ->setValue($this->_object->getTagId())
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
                            if(!$action->getValue()) return false;
                        }
                    }
                    if($actionTag == MageWorx_CustomerCredit_Model_Rules_Customer_Action::MAGEWORX_CUSTOMER_ACTION_REVIEW) {
                        foreach ($customerActions as $actionValue) {
                            if(!$skipUrl) {
                                $log->setId(null)
                                  ->setRuleId($actionValue['rule_id'])
                                  ->setCustomerId($customerId)
                                  ->setActionTag($actionTag)
                                  ->setValue($this->_object->getReviewId())
                                  ->save();
                            }

                            $action->setRuleId($actionValue['rule_id'])
                                ->setCustomerId($actionValue['customerId'])
                                ->setActionTag($actionValue['actionTag']);
                            if($result) {
                                $action->setValue($actionValue['rest']);
                            } else {   
                                $action->setValue($actionValue['nextValue']);
                                $action->save();
                                return false;
                            }
                            $action->save();
                        }
                    }
                } else {
                    if(!$skipUrl) {
                        $log->setId(null)
                          ->setRuleId($rule['rule_id'])
                          ->setCustomerId($this->_customer->getId())
                          ->setActionTag($actionTag)
                          ->setValue(time())
                          ->save();
                    }
                    else {
                        return false;
                    }
                }
                return true;
            }
        }
    }
    
    /**
     * Cechk rule aggregator
     * @param array $conditions
     * @param arary $success
     * @return boolean
     */
    private function _checkAggregator($conditions,$success) {
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
        return $result;
    }
    
    /**
     * Calculete credit value
     * @param MageWorx_CustomerCredit_Model_Rules $rule
     * @return MageWorx_CustomerCredit_Model_Rules
     */
    private function _calculateCredit($rule) {
        // if qty dependent
        if (isset($rule['qty_dependent']) && ($rule['qty_dependent']==1)) {
            $rule['credit'] = $rule['credit'] * $this->_ruleQty;
        }
        if(isset($this->_order) && (strpos($rule['credit'],'%')!==false)) {
                $rule['credit'] = (int) str_replace('%', '', $rule['credit']);
                $total = (float)$this->_order->getGrandTotal();
                if(!$total) {
                    $total = $this->_order->getSubtotalInvoiced();
                }
                $rule['credit'] = round($total*$rule['credit']/100,2);
            }
        return $rule;
    }
    
    /**
     * Send Credit Log to History
     * @param MageWorx_CustomerCredit_Model_Rules $rule
     * @return boolean
     */
    private function _sendLog($rule) {
        // if onetime
        $creditChange = 0;
        $store = Mage::app()->getStore();
        if($this->_object) {
            $store = Mage::app()->getStore($this->_object->getStoreId());
        }
        if($this->_object && !$store) {
            $store = Mage::app()->getStore($this->_object->getFirstStoreId());
        }
        if($this->_order) {
            $store = Mage::app()->getStore($this->_order->getStoreId());
        }
        $websiteId = $store->getWebsiteId();
        
        if (isset($rule['is_onetime'])) $isOnetime = $rule['is_onetime']; else $isOnetime = 1;

        $rulesCustomer = Mage::getModel('customercredit/rules_customer')->loadByRuleAndCustomer($rule['rule_id'], $this->_customer->getId());

        if (!$rulesCustomer || !$rulesCustomer->getId()) {
            $rulesCustomer = Mage::getModel('customercredit/rules_customer')->setRuleId($rule['rule_id'])->setCustomerId($this->_customer->getId())->save();                    
        } else {
            if ($isOnetime) return;
        }                
        
        if($this->_order) {
            $action = MageWorx_CustomerCredit_Model_Credit_Log::ACTION_TYPE_CREDITRULE;
            $order = $this->_order;
            $creditLog = Mage::getModel('customercredit/credit_log')->loadByOrderAndAction($this->_order->getId(), 3, $rulesCustomer->getId());                    
            if (!$creditLog || !$creditLog->getId()) {
                $creditChange = $rule['credit'];
                if(strpos($creditChange,"%")!==false) {
                    $creditChange = str_replace("%", "",$creditChange);
                    $total = 0;
                    $total = $this->_order->getGrandTotal();
                    if($total == 0) {
                        $total = $this->_order->getSubtotalInclTax() + $this->_order->getShippingInclTax();
                    }
                    $creditChange = $total*$creditChange/100;
                }
            
            }
        } else {
            $action = MageWorx_CustomerCredit_Model_Credit_Log::ACTION_TYPE_CREDIT_ACTION;
            $creditLog = Mage::getModel('customercredit/credit_log');  
            $order = NULL;
            $creditChange = (float)$rule['credit'];
        }
        Mage::getModel('customercredit/credit')
            ->setCustomerId($this->_customer->getId())
            ->setWebsiteId($websiteId)
            ->setOrder($order)
            ->setRuleId($rule['rule_id'])
            ->setRuleName($rule['name'])
            ->setRulesCustomerId($rulesCustomer->getId())                            
            ->setValueChange($creditChange)
            ->setActionType($action)
            ->save();
    }

    /**
     * Prepare credit rule to check
     * @param Varien_Event_Observer $observer
     * @return boolean
     */
    public function customercreditRule(Varien_Event_Observer $observer){    	       
        $order = $observer->getEvent()->getOrder();
        $this->_order = $order;
        if(Mage::registry('cc_order_refund')) {
            return true;
        }
        if ($customerId = $order->getCustomerId()) {
            $store = $order->getStore();
            $customer = Mage::getModel('customer/customer')->setStore($store)->load($customerId);
            $customerGroupId = $customer->getGroupId();
            $this->_customer = $customer;
	    $websiteId = $store->getWebsiteId();
	    $ruleModel = Mage::getResourceModel('customercredit/rules_collection');                                    
            $orderQty  = 0;//$order->getTotalQtyOrdered();
	    $ruleModel->setValidationFilter($websiteId, $customerGroupId)->setRuleTypeFilter(MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_GIVE);
	    foreach ($ruleModel->getData() as $rule) {                                
	    	$this->_executeRule($rule);
	    }
    	}
    }
    
    /**
     * Return credit value to balance if order cancel
     * @param Varien_Event_Observer $observer
     * @return MageWorx_CustomerCredit_Model_Observer
     */
    public function returnCredit(Varien_Event_Observer $observer) {                                                                            
        Mage::getModel('customercredit/credit')->cancel($observer->getEvent()->getOrder());                
        return $this;        
    }
    
    /**
     * Place order after
     * @param Varien_Event_Observer $observer
     * @return MageWorx_CustomerCredit_Model_Observer
     */
    public function placeOrderAfter(Varien_Event_Observer $observer) {
        $order = $observer->getEvent()->getOrder();
        $needUseCreditMarker = NULL;
        $needUseCreditMarker = Mage::registry('need_reduce_customercredit');
        $total_amount = $order->getCustomerCreditAmount();
        if($needUseCreditMarker) {
            $total_amount = $needUseCreditMarker;
        }
        Mage::register('customer_credit_order_place_amount_value',$total_amount,true);
        if ($this->reduceCustomerCreditValue($observer)) {
            // if payment of credit is fully -> invoice
            if ((Mage::helper('customercredit')->isEnabledInvoiceOrder() && $order->getBaseTotalDue()==0 && $order->canInvoice()) ||
                ($order->canInvoice() && $needUseCreditMarker)    
                    ) {                
                $savedQtys = array();
                foreach ($order->getAllItems() as $orderItem) {
                    $savedQtys[$orderItem->getId()] = $orderItem->getQtyToInvoice();
                }
                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($savedQtys);
                if (!$invoice->getTotalQty()) return $this;                

                if(!$needUseCreditMarker) {
                     $baseGrandTotal = $invoice->getBaseGrandTotal() - $invoice->getBaseCustomerCreditAmount();
                     $grandTotal = $invoice->getGrandTotal() - $invoice->getCustomerCreditAmount();
                     $invoice->setBaseGrandTotal($baseGrandTotal);
                     $invoice->setGrandTotal($grandTotal);
                 }
                $invoice->register();
                $invoice->getOrder()->setIsInProcess(true);
                
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                try {
                $transactionSave->save();                
                } catch (Exception $e) {
                    
                }
            }
        }
        $this->placeOrderCustomer($order);
        Mage::getSingleton('customer/session')->unsCustomCreditValue();
        $session = Mage::getSingleton('checkout/session');
        $session->setUseInternalCredit(false);
        return $this;  
    }       
    
    /**
     * Check if status order compleate
     * @param Varien_Event_Observer $observer
     * @return MageWorx_CustomerCredit_Model_Observer
     */
    public function checkCompleteStatusOrder(Varien_Event_Observer $observer) {
        $order = $observer->getEvent()->getOrder();
        if ($order->getStatus()=='complete') {            
            $creditProductSku = Mage::helper('customercredit')->getCreditProductSku();
            $creditQty = 0;
            if ($creditProductSku) {
                $allItems = $order->getAllItems();
                foreach ($allItems as $item) {
                    if ($item->getSku()==$creditProductSku) {
                        $creditQty = intval($item->getQtyInvoiced());
                    }
                }
                if ($creditQty>0) {                    
                    $creditLog = Mage::getModel('customercredit/credit_log')->loadByOrderAndAction($order->getId(), 5);                    
                    if (!$creditLog || !$creditLog->getId()) {                    
                        Mage::getModel('customercredit/credit')
                            ->setCustomerId($order->getCustomerId())
                            ->setWebsiteId($order->getStore()->getWebsiteId())
                            ->setOrder($order)
                            ->setValueChange($creditQty)
                            ->setActionType(MageWorx_CustomerCredit_Model_Credit_Log::ACTION_TYPE_CREDIT_PRODUCT)
                            ->save();
                    }    
                }
            }
            $this->customercreditRule($observer);
        }    
        return $this;        
    }
    /**
     * Add customercredit link to head
     * @param Varien_Event_Observer $observer
     */
    public function toHtmlBlockBefore(Varien_Event_Observer $observer) {
        $block = $observer->getEvent()->getBlock();
        $blockName = $block->getNameInLayout();
        if ($blockName == 'customer_account_navigation') {
            if (Mage::helper('customercredit')->isShowCustomerCredit()) $block->addLink('customercredit', 'customercredit', Mage::helper('customercredit')->__('My Credit'),array("_secure"=>true));
        } 
    }
    
    /**
     * Check is can partitial payment
     * @return boolean
     */
    public function isPartialPayment() {
        return Mage::helper('customercredit')->isPartialPayment(Mage::getSingleton('checkout/session')->getQuote(), Mage::getSingleton('customer/session')->getCustomerId(), Mage::app()->getStore()->getWebsiteId());        
    }        

    /**
     * Get credit value
     * @return float
     */
    public function getCreditValue() {
        return $this->_creditValue;
    }

    /**
     * Add html blocks to layaut
     * @param Varien_Event_Observer $observer
     */
    public function toHtmlBlockAfter($observer) {
        $block = $observer->getEvent()->getBlock();
        $transport = $observer->getEvent()->getTransport();
        
        if (!Mage::registry('customercredit_coupon_block') && $block instanceof Mage_Checkout_Block_Cart_Coupon && Mage::app()->getRequest()->getModuleName()!='firecheckout') {
            Mage::register('customercredit_coupon_block',true,true);
            $html = '';
            $partialPayment = $this->isPartialPayment();
            $this->_creditValue = Mage::helper('customercredit')->getCreditValue(Mage::getSingleton('customer/session')->getCustomerId(), Mage::app()->getStore()->getWebsiteId());
            if (Mage::helper('customercredit')->isDisplayCreditBlockAtCart() && $partialPayment!=-3){
            $html .= '<div class="credit-payment box discount"><h4>'.Mage::helper('core')->__('Payment with Credit').'</h4>';
                if(Mage::getModel('checkout/session')->getUseInternalCredit()) {
                    $quote = Mage::getModel('checkout/cart')->getQuote();
                    if (!$quote->isVirtual()) {
                        $address = $quote->getShippingAddress();
                    } else {
                        $address = $quote->getBillingAddress();
                    }
                    $html .= Mage::helper('core')->__('You are using %s your credits to pay this order.',$address->getCustomerCreditAmount()*Mage::getStoreConfig('mageworx_customers/customercredit_credit/exchange_rate')).'<br/>';
                    $html .= '<a href="'.Mage::getUrl('customercredit/index/removeCreditUse').'">'.Mage::helper('core')->__("Don't use credit.").'</a>';
                    $customBlock = Mage::app()->getLayout()->createBlock("customercredit/checkout_cartvalue", 'custom_value');
                    $customBlock->setTemplate('customercredit/checkout/custom_value_cart.phtml');
                    $blockHtml = $customBlock->toHtml();
                    $blockHtml = str_replace("<div id='fakeCCDiv' style='display:none;'>","",$blockHtml);
                    $blockHtml = str_replace("</script></div>","</script>",$blockHtml);
                    $html .= $blockHtml;
                    
                } elseif ($partialPayment>0) {
                    $html .='<form action="'.Mage::getUrl('customercredit/index/updateCreditPost').'" method="post" id="credit-payment">
                        <p>'.Mage::helper('core')->__('Available credit amount: %s', round($this->getCreditValue(),2)).' '.Mage::helper('core')->__('credits').' ('.Mage::helper('core')->currency($this->getCreditValue()/Mage::getStoreConfig('mageworx_customers/customercredit_credit/exchange_rate')).')</p>
                        <button type="submit" class="button"><span><span>'.Mage::helper('core')->__('Use Credit').'</span></span></button>
                    </form>';
                } else {
                    $html.='<p>'.Mage::helper('core')->__('Available credit amount: %s', $this->getCreditValue()).' '.Mage::helper('core')->__('credits').' ('.Mage::helper('core')->currency($this->getCreditValue()/Mage::getStoreConfig('mageworx_customers/customercredit_credit/exchange_rate')).')</p>' . Mage::helper('core')->__('Your credit amount is not enough.').'</p>
                    <button type="button" class="button" onclick="setLocation(\''.Mage::getUrl('customercredit/').'\')"><span><span>'.Mage::helper('core')->__('Get Credit').'</span></span></button>';
                }
            $html .= '</div>';

            $html .= $transport->getHtml();
            $transport->setHtml($html);
            }
        }
        if($block instanceof Mage_Payment_Block_Info) {
            $html = $transport->getHtml();
            if($block->getInfo()->getQuote() && $block->getInfo()->getQuote()->getPayment()->getMethodInstance()->getCode()=="customercredit") return;
            $session           = Mage::getSingleton('checkout/session');
            $useInternalCredit = $session->getUseInternalCredit();
            if($useInternalCredit && (Mage::app()->getRequest()->getControllerName()!='order')) {
                $html .= " & ".Mage::helper("customercredit")->__("Customer Credit");
            }
            $transport->setHtml($html);
        }
        if($block instanceof Mage_Catalog_Block_Product_Price) {
            $html = $transport->getHtml();
            $html = $this->addNotice($block,$html);
            $transport->setHtml($html);
        }
    }
    
    /**
     * Change customer group observer
     * @param Varien_Event_Observer $observer
     */
    
    public function changeGroup($observer) {
        $customer = $observer->getEvent()->getCustomer();
        if($customer->hasDataChanges()) {
       //     $newValues = array_diff($customer->getData(), $customer->getOrigData());
            $websiteId = $customer->getWebsiteId();
            if($customer->getData('group_id')!=$customer->getOrigData('group_id')) {
                $customerGroup = $customer->getData('group_id');
                $time = Mage::getStoreConfig('mageworx_customers/customercredit_expiration/default_expiration_period');
                if(Mage::getStoreConfig('mageworx_customers/customercredit_expiration/default_expiration_period_'.$customerGroup)) {
                    $time = Mage::getStoreConfig('mageworx_customers/customercredit_expiration/default_expiration_period_'.$customerGroup);
                }
                $credit = Mage::getModel('customercredit/credit')
                    ->setCustomerId($customer->getId())
                    ->setWebsiteId($websiteId)
                    ->loadCredit();
                if(!$time) {
                    $credit->setData('expiration_time',"0000-00-00");
                } else {
                    $credit->setData('expiration_time',date('Y-m-d',time()+3600*24*$time));
                }
                
                $credit->setIsCron(true)->save();
            }
        }
    }
    
    /**
     * Add Customer Subscribe Rule
     * @param Varien_Event_Observer $observer
     * @return boolean|MageWorx_CustomerCredit_Model_Observer
     */
    public function subscribeCustomer($observer) {
        $customer = $observer->getEvent()->getCustomer();
        $this->_customer = $customer;
        if (Mage::app()->getRequest()->getParam('is_subscribed') && ($customer->getIdSubscribed()==1))
        {
            return true;
        }
        $customerId = $customer->getId();
        if(!$customerId OR Mage::app()->getRequest()->getParam('is_subscribed') == 0)
        {
            return true;
        }
        $customerGroupId = $customer->getGroupId();

        $store = Mage::app()->getStore();
        $websiteId = $store->getWebsiteId();

        $ruleModel = Mage::getResourceModel('customercredit/rules_collection');                                    
        $ruleModel->setValidationFilter($websiteId, $customerGroupId)->setRuleTypeFilter(MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_GIVE);
        
        foreach ($ruleModel->getData() as $rule) {
            $this->_executeRule($rule);
        }
        return $this;
    }
    
    /**
     * Add Customer Tag Rule
     * @param Varien_Event_Observer $observer
     * @return boolean|MageWorx_CustomerCredit_Model_Observer
     */
    public function checkCustomerTagRule($observer) {
        $object = $observer->getObject();
        $customerId = $object->getData('first_customer_id');
        $actionName = $observer->getEvent()->getName();
        if(!$customerId || ($object->getStatus()!=1))
        {
            return true;
        }
       
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $this->_customer    = $customer;
        $this->_object      = $object;
        $customerGroupId    = $customer->getGroupId();

        $store = Mage::app()->getStore();
        $websiteId = $customer->getWebsiteId();
        
        $ruleModel = Mage::getResourceModel('customercredit/rules_collection');                                    
        $ruleModel->setValidationByCustomerGroup($customerGroupId);
        
        foreach ($ruleModel->getData() as $rule) {   
            $this->_executeRule($rule);
        }
       
        return $this;
    }
    
    /**
     * Add Customer Review Rule
     * @param Varien_Event_Observer $observer
     * @return boolean|MageWorx_CustomerCredit_Model_Observer
     */
    public function checkCustomerReviewRule($observer) {
        $object = $observer->getObject();
        $customerId = $object->getCustomerId();
        $actionName = $observer->getEvent()->getName();
        
        if(!$customerId || ($object->getStatusId()!=1))
        {
            return true;
        }
        
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $this->_customer    = $customer;
        $this->_object      = $object;
        $customerGroupId = $customer->getGroupId();

        $store = Mage::app()->getStore();
        $websiteId = $customer->getWebsiteId();
        
        $ruleModel = Mage::getResourceModel('customercredit/rules_collection');                                    
        $ruleModel->setValidationByCustomerGroup($customerGroupId);
        
        foreach ($ruleModel->getData() as $rule) {   
            $this->_executeRule($rule);
        }
        return $this;
    }
    
    /**
     * Add Customer Registration Rule
     * @param Varien_Event_Observer $observer
     * @return MageWorx_CustomerCredit_Model_Observer
     */
    public function customerRegisterSuccess($observer) {
        $success = array();
        
        $customer = $observer->getEvent()->getCustomer();
        $this->_customer = $customer;
        $customerId = $customer->getId();
        
        $customerGroupId = $customer->getGroupId();
        
        $store = Mage::app()->getStore();
        $websiteId = $store->getWebsiteId();
        $ruleModel = Mage::getResourceModel('customercredit/rules_collection');                                    

        $ruleModel->setValidationFilter($websiteId, $customerGroupId)->setRuleTypeFilter(MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_GIVE);
        foreach ($ruleModel->getData() as $rule) {                                
            $this->_executeRule($rule);
        }
        return $this;
    }
    
    /**
     * Check Date of Birth Rule
     * @return boolean
     */
    public function dobCustomerCron() {
        $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('dob')->addAttributeToFilter('dob',array('like' => '%-'.date('m-d', time()).' %'));
        foreach($collection->getItems()as $customer)
        {
            try {
                $this->dobCustomer($customer);
            } catch (Exception $e) {
                Mage::log($e->getMessage());
            }
        }
        return true;
    }
    
    /**
     * Exec Date of Birth Rule
     * @param Mage_Customer_Model_Customer $customer
     * @return MageWorx_CustomerCredit_Model_Observer
     */
    public function dobCustomer($customer) {
        
        $this->_customer = $customer;
        
        $customerId = $customer->getId();
        $customerGroupId = $customer->getGroupId();

        $store = Mage::app()->getStore($customer->getStoreId());
        $websiteId = $store->getWebsiteId();

        $ruleModel = Mage::getResourceModel('customercredit/rules_collection');                                    
        $ruleModel->setValidationFilter($websiteId, $customerGroupId)->setRuleTypeFilter(MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_GIVE);
        
        foreach ($ruleModel->getData() as $rule) {                                
            $this->_executeRule($rule);
        }
        return $this;
    }
    
    /**
     * Add rules to observer
     * @param Mage_Sales_Model_Order $order
     * @return MageWorx_CustomerCredit_Model_Observer
     */
    public function placeOrderCustomer($order) {
        if(!$order->getQuote()) return; 
        $customer = $order->getQuote()->getCustomer();
        $this->_customer = $customer;
        $this->_order = $order;
        $customerId = $customer->getId();
        $customerGroupId = $customer->getGroupId();
        
        $store = Mage::app()->getStore($customer->getStoreId());
        $websiteId = $store->getWebsiteId();

        $ruleModel = Mage::getResourceModel('customercredit/rules_collection');                                    
        $ruleModel->setValidationFilter($websiteId, $customerGroupId)->setRuleTypeFilter(MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_GIVE);
        foreach ($ruleModel->getData() as $rule) {
            $this->_executeRule($rule);
        }
        return $this;
    }
    
    /**
     * Change expiration time if customer change group
     * @param type $observer
     */
    public function customerGroupSaveAfter($observer) {
        $days = Mage::app()->getRequest()->getParam('customercredit_expiration_in');
        Mage::getModel('core/config')->saveConfig('mageworx_customers/customercredit_expiration/default_expiration_period_'.Mage::app()->getRequest()->getParam('id'),$days);
        $this->expirationDateRefreshCron();
    }
    
    /**
     * Change expiration time for customer group
     * @param type $observer
     */
    public function customerGroupLoadAfter($observer) {
        $groupModel = $observer->getEvent()->getObject();
        $days = Mage::getStoreConfig('mageworx_customers/customercredit_expiration/default_expiration_period_'.$groupModel->getCustomerGroupId());
        $groupModel->setData('customercredit_expiration_in',$days);
        return $groupModel;
    }
    
    /**
     * Add html to block
     * @param type $observer
     * @return type
     */
    public function customerGroupPrepareLayoutAfter($observer) {
        $block = $observer->getBlock();
        if($block->getType()!='adminhtml/customer_group_edit_form') return ;
        $customerGroup = Mage::registry('current_group');

        $form = $block->getForm();
        $fieldset = $form->getElement('base_fieldset');
        $element = $fieldset->addField('customercredit_expiration_in', 'text',
            array(
                'name'  => 'customercredit_expiration_in',
                'label' => Mage::helper('customer')->__('Customer Credit Expiration In'),
                'title' => Mage::helper('customer')->__('Customer Credit Expiration In'),
                'note'  => Mage::helper('customer')->__('day(s)'),
            )
        );
        $element->setValue($customerGroup->getData($element->getId()));

        $block->setForm($form);
    }

    /**
     * Check customercredit expiration time by cron
     * @return boolean
     */
    public function expirationDateCron() {
        $today = strtotime(date("Y-m-d"));
        if(!Mage::getStoreConfig('mageworx_customers/customercredit_expiration/expiration_enable')) return ;
        $model=Mage::getModel('customercredit/credit');
        $collection = $model->getCollection()->joinCustomerTable();
        $sendCustomerNotificationPeriod = Mage::getStoreConfig('mageworx_customers/customercredit_expiration/notify_expiration_date_left');
        foreach ($collection as $item) {
            
            if($item->getExpirationTime()!='0000-00-00') {
                $date = strtotime($item->getExpirationTime());
                $hash = ($date-$today)/(3600*24);
                if(($hash==$sendCustomerNotificationPeriod) && ($item->getValue()>0)) {
                    Mage::helper('customercredit')->sendNotificationExpiration($item->getCustomerId(),$sendCustomerNotificationPeriod);
                }
                if($hash==0) {
                    $item->setValueChange(0-$item->getValue());
                    $item->getLogModel()->setActionType(MageWorx_CustomerCredit_Model_Credit_Log::ACTION_TYPE_EXPIRED);     
                    $item->save();
                }
            }
        }
    }
    
    /**
     * Add new task to cron when expiration time changed
     */
    public function expirationDateRefreshCron() {
        $model=Mage::getModel('customercredit/credit');
        $collection = $model->getCollection()->joinCustomerTable();
        $isRefreshAll = Mage::getStoreConfig('mageworx_customers/customercredit_expiration/update_expiration_date');
        foreach ($collection as $item) {
            $customerGroup =$item->getGroupId();
            $time = Mage::getStoreConfig('mageworx_customers/customercredit_expiration/default_expiration_period');
            if(Mage::getStoreConfig('mageworx_customers/customercredit_expiration/default_expiration_period_'.$customerGroup)) {
                $time = Mage::getStoreConfig('mageworx_customers/customercredit_expiration/default_expiration_period_'.$customerGroup);
            }
            
            if(($item->getExpirationTime()=='0000-00-00') || $isRefreshAll) {
                if(!$time) {
                    $item->setData('expiration_time',"0000-00-00");
                } else {
                    $item->setData('expiration_time',date('Y-m-d',time()+3600*24*$time));
                }
            }
        
            $item->setIsCron(true)->save();
        }
    }
    
    public function addNotice($block,$html) {
        if(Mage::registry('notice_added')) return $html;
        if(!Mage::getSingleton('customer/session')->isLoggedIn()) return $html;
        if(!Mage::registry('current_product')) return $html;
        Mage::register('notice_added',true,true);
        $notice = NULL;
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ($customerId = $customer->getId()) {
            $store = Mage::app()->getStore();
            $product = Mage::registry('current_product');
            $customer = Mage::getModel('customer/customer')->setStore($store)->load($customerId);
            $customerGroupId = $customer->getGroupId();
            $websiteId = $store->getWebsiteId();
	    $ruleModel = Mage::getResourceModel('customercredit/rules_collection');                                    
            $ruleModel->setValidationFilter($websiteId, $customerGroupId)->setRuleTypeFilter(MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_APPLY);
	     foreach ($ruleModel->getData() as $rule) {           
                $conditions = unserialize($rule['conditions_serialized']);
                foreach ($conditions['conditions'] as $condition) {
                 // echo "<pre>";  print_r($conditions); exit;
                    $values = explode(',',$condition['value']);
                    if(($condition['attribute']=="sku") && (in_array($product->getSku(),$values))) {
                        $notice = '<div class="customercredit_notice">'.Mage::getStoreConfig('mageworx_customers/customercredit_credit/product_apply_notice').'</div>';
                        break;
                    }
                }
	    }
    	}
//        $notice = '<div class="customercredit_notice">'.Mage::helper('core')->__('This product can be paid using internal credits.').'</div>';
        return $html.$notice;
    }
}