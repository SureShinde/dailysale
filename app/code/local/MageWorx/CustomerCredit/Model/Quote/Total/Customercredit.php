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

class MageWorx_CustomerCredit_Model_Quote_Total_Customercredit extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    private $_creditMultiShippingValue = array();
    
    
    public function __construct() {
        $this->setCode('customercredit');
    }

    /**
     * Colelct Credit Total
     * @param Mage_Sales_Model_Quote_Address $address
     * @return MageWorx_CustomerCredit_Model_Quote_Total_Customercredit
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        if (!Mage::helper('customercredit')->isEnabled()) {
            return $this;
        }
        
        $post = Mage::app()->getRequest()->getPost();
        $quote = $address->getQuote();
        if (!$quote->getItemsCount()) {
            return $this;
        }
        
        if ($this->_getBaseQuoteAddressType($quote) != $address->getAddressType()) {
            $address->setCustomerCreditAmount(0);
            $address->setBaseCustomerCreditAmount(0);
            return $this;
        }
        
        // if multishipping
        $session           = Mage::getSingleton('checkout/session');
        $useInternalCredit = $session->getUseInternalCredit();
        $request = Mage::app()->getRequest();
        if($request->getControllerName()=='multishipping') {
            $params = $request->getParams();
            $payment = $request->getParam('payment');
            
            if(!isset($this->_creditMultiShippingValue[$address->getId()])) {
                $this->_creditMultiShippingValue[$address->getId()] = 0;
            } 
            if((isset($payment['use_internal_credit']) && (bool)$payment['use_internal_credit']) || (isset($payment['method']) && ($payment['method']=='customercredit'))) {
                Mage::getSingleton('checkout/session')->setUseInternalCredit(true);
                $useInternalCredit = 1;
            } else {
                 $action = Mage::app()->getRequest()->getActionName();
                if($action=="saveOrder" || $action=='overviewPost') {
                    if(!$useInternalCredit) {
                        Mage::getModel('checkout/session')->setUseInternalCredit(false);
                        $useInternalCredit = 0;
                    }
                } else {
                    Mage::getModel('checkout/session')->setUseInternalCredit(false);
                    $useInternalCredit = 0;
                }
            }
        }
        
        if(!$useInternalCredit && !Mage::app()->getStore()->isAdmin()) {
            $address->setCustomerCreditAmount(0);
            $address->setBaseCustomerCreditAmount(0);
            return $this;
        }
        if($request->getControllerName()=='multishipping') {
            if( Mage::registry('customer_credit_calculate_multishipping_'.$address->getId())) {
                return ;
            }
        }
//         echo $address->getSubtotal().'---';
        
        /**
         * === Rule condition start ===
         */
        
        $websiteId       = Mage::app()->getWebsite()->getId();
        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
        
        Mage::getSingleton('customer/session')->setData('customer_credit_rule',false);        
        $productConditionsPrice = array();
        $productConditionsPrice = Mage::helper('customercredit')->checkApplyCreditsSum($quote,$customerId,$websiteId);
        /**
         * === Rule condition end ===
         */
       
        $address->setCustomerCreditAmount(0);
        $address->setBaseCustomerCreditAmount(0);

        /**
         * @todo wtf variable?
         */
        if(!$request->getControllerName()=='multishipping') {
            $session->setUseInternalCredit(false);
        }
        $paymentData = Mage::app()->getRequest()->getPost('payment');
        $orderData = Mage::app()->getRequest()->getPost('order');
        
        /**
         * @todo need to be reviwed
         * It seems that $quote->getItemsCount() do the same in the beginning of a method
         */
        if((Mage::getModel('checkout/cart')->getQuote()->getData('items_qty')==0) && !Mage::getSingleton('adminhtml/session_quote')->getCustomerId()) {
            return $this;
        }
        /**
         * @todo make a condition more easy for reading
         */
        if(isset($paymentData['use_internal_credit']) && ($paymentData['use_internal_credit'] == 'customercredit')) {
            $request = Mage::app()->getRequest();
            $params = Mage::app()->getRequest()->getParams();
            $paymentData['use_internal_credit'] = (bool)$request->getParam('use_internal_credit',0);
        }
        if ($quote->getPayment()->getMethod()=='customercredit' || (isset($orderData['payment_data']) && $orderData['payment_data']=="customercredit") || isset($orderData['payment_method']) && ($orderData['payment_method']=="customercredit")
            || ($paymentData && (isset($paymentData['method']) && $paymentData['method'] == 'customercredit'))
            || ($paymentData && isset($paymentData['use_internal_credit']) && ($paymentData['use_internal_credit'] > 0))
            || ($useInternalCredit && Mage::getSingleton('customer/session')->getCustomerId() && !$paymentData)
            || ($useInternalCredit && Mage::getSingleton('customer/session')->getCustomerId() && ($request->getControllerName()=='multishipping'))
            ) {
            //$session->setUseInternalCredit(true);
        } else {
           
            return $this;
        }                              
        $baseCredit = (float)Mage::helper('customercredit')->getCreditValue($quote->getCustomerId(), Mage::app()->getStore($quote->getStoreId())->getWebsiteId());
        if ($baseCredit==0) {
            return $this;
        }
        
        if($request->getControllerName()=='multishipping') {
            if(!isset($this->_creditMultiShippingValue[$address->getId()]) || !$this->_creditMultiShippingValue[$address->getId()]) {
                $baseCredit -= array_sum($this->_creditMultiShippingValue);
            } else {
                $baseCredit = $this->_creditMultiShippingValue[$address->getId()];
            }
        }
        $baseCredit = $baseCredit/Mage::getStoreConfig('mageworx_customers/customercredit_credit/exchange_rate');
        
        $credit = (float)$quote->getStore()->convertPrice($baseCredit);

        $baseGrandTotal = $address->getBaseGrandTotal()?floatval($address->getBaseGrandTotal()-$address->getMwRewardpointDiscount()):floatval($address->getBaseSubtotal()-$address->getMwRewardpointDiscount());
        $grandTotal = $address->getGrandTotal()?floatval($address->getGrandTotal()-$address->getMwRewardpointDiscount()):floatval($address->getSubtotal()-$address->getMwRewardpointDiscount());
        if(!$baseGrandTotal) $baseGrandTotal = $address->getBaseSubtotal();
        if(!$baseGrandTotal) $grandTotal = $address->getSubtotal();
        
        $baseShipping = floatval($address->getBaseShippingAmount() - $address->getBaseShippingTaxAmount());
        $shipping = floatval($address->getShippingAmount() - $address->getShippingTaxAmount());
                
        $baseTax = floatval($address->getBaseTaxAmount());
        $tax = floatval($address->getTaxAmount());       
        
        
        if ($baseGrandTotal) $baseSubtotal = $baseGrandTotal - $baseShipping - $baseTax; else $baseSubtotal = floatval($address->getBaseSubtotalWithDiscount());
        if ($grandTotal) $subtotal = $grandTotal - $shipping - $tax; else $subtotal = floatval($address->getSubtotalWithDiscount());       
        
        $creditTotals = Mage::helper('customercredit')->getCreditTotals();
        if (count($creditTotals)<3) {
            $baseCreditLeft = 0;
            $creditLeft = 0;
            foreach ($creditTotals as $field) {
                switch ($field) {
                    case 'subtotal':                            
                        $baseCreditLeft += $baseSubtotal;
                        $creditLeft += $subtotal;
                        break;
                    case 'shipping':
                        $baseCreditLeft += $baseShipping;
                        $creditLeft += $shipping;                   
                        break;
                    case 'tax':
                        $baseCreditLeft += $baseTax;
                        $creditLeft += $tax;
                        break;  
                    case 'fees':
                        $baseCreditLeft += $address->getBaseMultifeesAmount();
                        $creditLeft += $address->getMultifeesAmount();
                        break;  
                }
            }
        } else {
            $baseCreditLeft = $baseGrandTotal;
            $creditLeft = $grandTotal;
        }
     
        
        // if authorizenet and orderspro_order_edit and credit => adjustment of GrandTotal
        if (Mage::app()->getStore()->isAdmin() && Mage::app()->getRequest()->getControllerName() == 'orderspro_order_edit' && $quote->getPayment()->getMethod() == 'authorizenet') {
            $orderIdPrev = Mage::getSingleton('adminhtml/sales_order_create')->getSession()->getOrderId();
            if ($orderIdPrev > 0) {
                $orderPrev = Mage::getModel('sales/order')->load($orderIdPrev);
                $paymentPrev = $orderPrev->getPayment();
                if ($paymentPrev->getMethod() == 'authorizenet' && $paymentPrev->getBaseAmountOrdered() > 0 && $address->getBaseGrandTotal() >= $paymentPrev->getBaseAmountOrdered()) {
                    $baseCreditLeft = $address->getBaseGrandTotal() - $paymentPrev->getBaseAmountOrdered();
                    $creditLeft = $address->getGrandTotal() - $paymentPrev->getAmountOrdered();
                }
            }
        }
        
        if(sizeof($productConditionsPrice)>0) {
            Mage::getSingleton('customer/session')->setData('customer_credit_rule',true);
            $sum = array_sum($productConditionsPrice);
            Mage::register('credit_appled_products_sum', array_sum($productConditionsPrice), TRUE);
            $baseCreditLeft = $sum;
            $creditLeft     = $sum;
        }
        
        
        $isEnabledPartialPayment = Mage::helper('customercredit')->isEnabledPartialPayment();
        if ($baseCredit < $baseCreditLeft) {
            if ($isEnabledPartialPayment) {
                $baseCreditLeft = $baseCredit;
                $creditLeft = $credit;
            } else {
                return $this;
            }    
        }
   
        
        if (!$baseCreditLeft) return $this;
        
        $action = Mage::app()->getRequest()->getActionName();
        if($credit-$creditLeft<0) {
            $baseCreditLeft=$baseCredit;
            $creditLeft = $credit;
        }
        
        $minOrder = Mage::getStoreConfig('mageworx_customers/customercredit_credit/min_order_amount');
        if(($address->getGrandTotal() - $creditLeft<=$minOrder)) {
            $baseCreditLeft -= $minOrder - ($address->getBaseGrandTotal() - $baseCreditLeft);
            $creditLeft -= $minOrder - ($address->getGrandTotal() - $creditLeft);;
        }
        
        /**
         * @todo FIX this horror! Later...
         */
       
        if($action=="saveOrder" || !Mage::getStoreConfig('mageworx_customers/customercredit_credit/reduce_grand_total')) {
            if(!Mage::getStoreConfig('mageworx_customers/customercredit_credit/reduce_grand_total')) {
                if(($address->getBaseGrandTotal() - $baseCreditLeft)<0.001) {
                    Mage::register('need_reduce_customercredit', $creditLeft, true);
                } else {
                    $address->setBaseCustomerCreditAmount($baseCreditLeft);
                    $address->setCustomerCreditAmount($creditLeft);
                    
                    if($request->getControllerName()=='multishipping') {
                        $creditLeft = 0 - abs($creditLeft);
                        $baseCreditLeft = 0 - abs($baseCreditLeft);
        //                  echo "----/".$creditLeft."/----";
                        $address->setSubtotalInclTax($address->getSubtotal() + $creditLeft + $shipping);
                        $address->setBaseSubtotalInclTax($address->getBaseSubtotal() + $baseCreditLeft + $shipping);

                        $address->setSubtotal($address->getSubtotal()+$creditLeft + $shipping+$tax);
                        $address->setBaseSubtotal($address->getBaseSubtotal() + $baseCreditLeft + $shipping+$tax);
                    } else {
                        $address->setBaseGrandTotal($address->getBaseGrandTotal() - $baseCreditLeft);
                        $address->setGrandTotal($address->getGrandTotal() - $creditLeft);
                    }
                }
            } else {
                $address->setBaseCustomerCreditAmount($baseCreditLeft);
                $address->setCustomerCreditAmount($creditLeft);

                if($request->getControllerName()=='multishipping') {
                        $creditLeft = 0 - abs($creditLeft);
                        $baseCreditLeft = 0 - abs($baseCreditLeft);
        //                  echo "----/".$creditLeft."/----";
                        $address->setSubtotalInclTax($address->getSubtotal() + $creditLeft + $shipping);
                        $address->setBaseSubtotalInclTax($address->getBaseSubtotal() + $baseCreditLeft + $shipping);

                        $address->setSubtotal($address->getSubtotal()+$creditLeft + $shipping+$tax);
                        $address->setBaseSubtotal($address->getBaseSubtotal() + $baseCreditLeft + $shipping+$tax);
                    } else {
                        $address->setBaseGrandTotal($address->getBaseGrandTotal() - $baseCreditLeft);
                        $address->setGrandTotal($address->getGrandTotal() - $creditLeft);
                    }
            }
        } else {
            
            /// MULTISHIPPING!
            $address->setBaseCustomerCreditAmount($baseCreditLeft);
            $address->setCustomerCreditAmount($creditLeft);

            $address->setBaseGrandTotal($address->getBaseGrandTotal() - $baseCreditLeft);
            $address->setGrandTotal($address->getGrandTotal() - $creditLeft); 
            
            if($request->getControllerName()=='multishipping') {
                $creditLeft = 0 - abs($creditLeft);
                $baseCreditLeft = 0 - abs($baseCreditLeft);
//                  echo "----/".$creditLeft."/----";
                if($action=='overviewPost') {
                    $creditLeft = $baseCreditLeft = 0;
                }
                $address->setSubtotalInclTax($address->getSubtotal() + $creditLeft + $shipping);
                $address->setBaseSubtotalInclTax($address->getBaseSubtotal() + $baseCreditLeft + $shipping);

                $address->setSubtotal($address->getSubtotal()+$creditLeft + $shipping+$tax);
                $address->setBaseSubtotal($address->getBaseSubtotal() + $baseCreditLeft + $shipping+$tax);
            } else {
                
            }
        }
        
        if($request->getControllerName()=='multishipping') {
            $baseCreditLeft = $baseCredit*Mage::getStoreConfig('mageworx_customers/customercredit_credit/exchange_rate');
            $this->_creditMultiShippingValue[$address->getId()] = $address->getBaseCustomerCreditAmount();
//            Mage::register('customer_credit_calculate_multishipping_'.$address->getId(),true,true);
//            echo $address->getSubtotal().'---'.$this->_creditMultiShippingValue[$address->getId()]."<br>";
        }
        $session->setUseInternalCredit(true);
//echo "<pre>"; print_r($this->_creditMultiShippingValue); echo "</pre>";
        return $this;
    }

    /**
     * fetch
     * @param Mage_Sales_Model_Quote_Address $address
     * @return MageWorx_CustomerCredit_Model_Quote_Total_Customercredit
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address) {   
        // Need to very bad extensions.
        $session = Mage::getSingleton('checkout/session');
        if(!$session->getUseInternalCredit() && !Mage::app()->getStore()->isAdmin()) {
           return $this;
        }
        
        if (!Mage::helper('customercredit')->isEnabled()) return $this;        

        if ($address->getCustomerCreditAmount()>0) {
            $address->addTotal(array(
                'code'=>$this->getCode(),
                'title'=>Mage::helper('customercredit')->__('Internal Credit'),
                'value'=>-$address->getCustomerCreditAmount(),
            ));
        }
        return $this;
    }
    
    /**
     * Return the address which will participate in quote total calculation
     * 
     * @param Mage_Sales_Model_Quote $quote
     */
    protected function _getBaseQuoteAddressType($quote) {
        $addressType = Mage_Sales_Model_Quote_Address::TYPE_BILLING;
        if (!$quote->isVirtual()) {
                $addressType = Mage_Sales_Model_Quote_Address::TYPE_SHIPPING;
        }
        return $addressType;
    }
}