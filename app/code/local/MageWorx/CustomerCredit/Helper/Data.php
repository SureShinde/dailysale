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

class MageWorx_CustomerCredit_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_isShowCustomerCreditFlag = null;
    
    /**
     * Is customer credit enabled
     * @return boolean
     */
    public function isEnabled() {
        return Mage::getStoreConfigFlag('mageworx_customers/customercredit_credit/enable_credit');
    }
    
    /**
     * Is credit share enabled
     * @return boolean
     */
    public function isEnableShareCredit() {
        if(Mage::getStoreConfigFlag('mageworx_customers/customercredit_credit/enable_sharing')) {
            return $this->isShowCustomerCredit();
        }
        return false;
    }
    
    /**
     * Check scope
     * @return boolean
     */
    public function isScopePerWebsite() {
        return Mage::getStoreConfigFlag('mageworx_customers/customercredit_credit/credit_scope');
    }
    
    /**
     * DEPRICATED
     * @return boolean
     */
    public function isHideCreditUntilFirstUse() {
        return Mage::getStoreConfigFlag('mageworx_customers/customercredit_credit/hide_credit_until_first_use');
    }
    
    /**
     * Is credit code enabled
     * @return boolean
     */
    public function isEnabledCodes() {
        return Mage::getStoreConfigFlag('mageworx_customers/customercredit_recharge_codes/enable_recharge_codes');
    }

    /**
     * Is autoinvoce
     * @return boolean
     */
    public function isEnabledInvoiceOrder() {
        return Mage::getStoreConfig('mageworx_customers/customercredit_credit/enable_invoice_order');
    }
    
    /**
     * Is enable partitial payment
     * @return boolean
     */
    public function isEnabledPartialPayment() {
        return Mage::getStoreConfigFlag('mageworx_customers/customercredit_credit/enable_partial_credit_payment');
    }
    
    /**
     * Can return creditmemo to credits
     * @return boolean
     */
    public function isEnabledCreditMemoReturn() {
        if(Mage::getStoreConfigFlag('mageworx_customers/customercredit_credit/enable_credit_memo_return')) {
            return $this->isShowCustomerCredit();
        }
        return false;
    }
    
    /**
     * Display credit block in cart
     * @return boolean
     */
    public function isDisplayCreditBlockAtCart() {
        if(Mage::getStoreConfigFlag('mageworx_customers/customercredit_credit/display_credit_block_at_cart')) {
            return $this->isShowCustomerCredit();
        }
        return false;
    }
        
    /**
     * Is added credit column in order grid
     * @return boolean
     */
    public function isEnabledCreditColumnsInGridOrderViewTabs() {
        return Mage::getStoreConfigFlag('mageworx_customers/customercredit_credit/enable_credit_columns_in_grid_order_view_tabs');
    }
    
    /**
     * Is added credit column in customer grid
     * @return boolean
     */
    public function isEnabledCustomerBalanceGridColumn() {
        return Mage::getStoreConfigFlag('mageworx_customers/customercredit_credit/enable_customer_balance_grid_column');
    }
    
    /**
     * Can send notifications when credit balance changed
     * @return boolean
     */
    public function isSendNotificationBalanceChanged() {
        return Mage::getStoreConfigFlag('mageworx_customers/customercredit_email_config/send_notification_balance_changed');
    }
    
    /**
     * Get totals
     * @return array
     */
    public function getCreditTotals() {
        return explode(',', Mage::getStoreConfig('mageworx_customers/customercredit_credit/credit_totals'));
    }
    
    /**
     * Get default QTY for credit product
     * @return int
     */
    public function getDefaultQtyCreditUnits() {
        return Mage::getStoreConfig('mageworx_customers/customercredit_credit/default_qty_credit_units');        
    }
    
    /**
     * Get credti product sku
     * @return string
     */
    public function getCreditProductSku() {
        return Mage::getStoreConfig('mageworx_customers/customercredit_credit/credit_product');        
    }    
        
    /**
     * DEPRICATED
     * @return json
     */
    public function getJsCurrency() {
        $websiteCollection = Mage::getSingleton('adminhtml/system_store')->getWebsiteCollection();
        $currencyList = array();
        foreach ($websiteCollection as $website)
        {
            $currencyList[$website->getId()] = $website->getBaseCurrencyCode();
        }
        return Zend_Json::encode($currencyList);
    }
    
    /**
     * Get sales address
     * @param Mage_Sales_Model_Quote $quote
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getSalesAddress($quote) {
        $address = $quote->getShippingAddress();
        if ($address->getSubtotal()==0) {
            $address = $quote->getBillingAddress();
        }
        return $address;
    }
    
    /**
     * Check apply credits and return array of values
     * @param Mage_Sales_Model_Quote $quote
     * @param int $customerId
     * @param int $websiteId
     * @return array
     */
    public function checkApplyCreditsSum($quote, $customerId, $websiteId) {
     //   return true;
        $result = array();
        $store = Mage::app()->getStore();
        $customer = Mage::getModel('customer/customer')->setStore($store)->load($customerId);
        $customerGroupId = $customer->getGroupId();
        $websiteId = $store->getWebsiteId();
        $ruleModel = Mage::getResourceModel('customercredit/rules_collection');                                    
        $ruleModel->setValidationFilter($websiteId, $customerGroupId)->setRuleTypeFilter(MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_APPLY);
        foreach ($ruleModel->getData() as $rule) {    
            if(!count($result)) {
                $result = array(0);
            }
            $conditions = unserialize($rule['conditions_serialized']);                 
            if ($conditions) {
                $conditionModel = Mage::getModel('customercredit/rules_condition_combine')->setPrefix('conditions')->loadArray($conditions);
                $_result = $conditionModel->validate($this->getSalesAddress($quote),TRUE);
                if(is_array($_result)) {
                    $result = array_merge($result,$_result);
                } 
            }
        }
        return $result;
    }
    
    public function getAllBillingAddresses($quote) {
        $addresses = array();
        foreach ($quote->getAddressesCollection() as $address) {
            if ($address->getAddressType()==Mage_Sales_Model_Quote_Address::TYPE_SHIPPING
                && !$address->isDeleted()) {
                $addresses[] = $address;
            }
        }
        return $addresses;
    }

    /**
     * Get partitial payment type
     * @param Mage_Sales_Model_Quote $quote
     * @param int $customerId
     * @param int $websiteId
     * @return boolean|int
     *  -3 - can't apply credits
     *  -2 - hide customer credit
     *  -1 - no balabce checkbox
     *  0 - no balance radio
     *  1 - checkbox (partial payment)
     *  2 - radio (full payment)
     */
    public function isPartialPayment($quote, $customerId = null, $websiteId = null) {
        if (!$this->isShowCustomerCredit()) { return -2; }
        if (!$quote) { return -2; }
        
        if(Mage::app()->getStore()->isAdmin()) {
            $customerId = Mage::getSingleton('adminhtml/session_quote')->getCustomerId();
        }
     
        if(!$customerId){
            if($customer = Mage::getSingleton('customer/session')){
                $customerId = $customer->getEntityId();
            } else {
                return false;
            }
        }

        $value = $this->getCreditValue($customerId, $websiteId);
        $value = $value/Mage::getStoreConfig('mageworx_customers/customercredit_credit/exchange_rate');
        $isEnabledPartialPayment = $this->isEnabledPartialPayment();
        
        if ($value==0) {
            if ($isEnabledPartialPayment) return -1; else return 0;
        }        
        
        // check apply credits
        $productConditionsPrice = $this->checkApplyCreditsSum($quote, $customerId, $websiteId);
        if(sizeof($productConditionsPrice)>0 && !array_sum($productConditionsPrice)) {
               return -3;
        }
        
        if(Mage::getSingleton('customer/session')->getData('customer_credit_rule')) {
            return 1;
        }
        if (Mage::app()->getStore()->isAdmin()) {
            $allItems = $quote->getAllItems();
            $productIds = array();
            foreach ($allItems as $item) {
                $productIds[] = $item->getProductId();
            }
        } else {
            $productIds = Mage::getSingleton('checkout/cart')->getProductIds();            
        }
        
        $addressType = Mage_Sales_Model_Quote_Address::TYPE_BILLING;
        $creditProductSku = $this->getCreditProductSku();
        foreach ($productIds as $productId) {
            $product = Mage::getModel('catalog/product')->load($productId);
            if (!$product) continue;
            // is credit product - no credit!
            if ($creditProductSku && $product->getSku()==$creditProductSku) return 0;
            
            $productTypeId = $product->getTypeId();
            if ($productTypeId!='downloadable' && !$product->isVirtual()) {
                $addressType = Mage_Sales_Model_Quote_Address::TYPE_SHIPPING;
                break;
            }
        }
    
        //shipping or billing
        if ($addressType==Mage_Sales_Model_Quote_Address::TYPE_SHIPPING) {
            $addresses = $quote->getAllShippingAddresses();
        } else {
            $addresses = $this->getAllBillingAddresses($quote);
        }
        
        $subtotal = 0;
        $shipping = 0;
        $tax = 0;
        $grandTotal = 0;
        $tail = 0;
        foreach ($addresses as $address) {
            ///////
            $subtotal = floatval($address->getBaseSubtotalWithDiscount()-$address->getMwRewardpointDiscount()); //$address->getBaseSubtotal();
            $shipping = floatval($address->getBaseShippingAmount() - $address->getBaseShippingTaxAmount());
            $tax = floatval($address->getBaseTaxAmount());

            $grandTotal = floatval($quote->getBaseGrandTotal() + $address->getBaseCustomerCreditAmount());
            if ($grandTotal==0) $grandTotal = floatval(array_sum($address->getAllBaseTotalAmounts()));
            if ($grandTotal==0) $grandTotal = $subtotal + $shipping + $tax;
//            echo $subtotal.'|'.$shipping.'|'.$tax.'|='.$grandTotal.'<br/>';        
            $tail = $grandTotal;
        ///////
        }
        $creditTotals = $this->getCreditTotals();
        if (count($creditTotals)<3) {
            $amount = 0;
            foreach ($creditTotals as $field) {
                switch ($field) {
                    case 'subtotal':                            
                        $amount += $subtotal;
                        $tail -= $subtotal;
                        break;
                    case 'shipping':
                        $amount += $shipping;
                        $tail -= $shipping;                    
                        break;
                    case 'tax':
                        $amount += $tax;
                        $tail -= $tax;
                        break;  
                    case 'fees':
                        $baseCreditLeft += $address->getBaseMultifeesAmount();
                        $creditLeft += $address->getMultifeesAmount();
                        break;  
                }
            }
        } else {
            $amount = $grandTotal;
            $tail = 0;            
        }
        
        if(sizeof($productConditionsPrice)>0) {
            $sum = array_sum($productConditionsPrice);
            $baseCreditLeft = $sum;
            $creditLeft     = $sum;
        }
        
        $amount = round($amount, 2);
        $tail = round($tail, 2);        
//        echo $amount.'|'.$tail.'|'.$value; //exit;
        
        if ($value >= $amount && $tail==0) {
            $minOrder = Mage::getStoreConfig('mageworx_customers/customercredit_credit/min_order_amount');
            if($minOrder && ($amount - $value<$minOrder)) {
                if ($isEnabledPartialPayment) return 1; else return 0;
            }
            return 2;
        } else {
            if ($isEnabledPartialPayment) return 1; else return 0;
        }
    }
    
    /**
     * Send email
     * @param Mage_Customer_Model_Customer $customer
     * @return MageWorx_CustomerCredit_Helper_Data
     */
    public function sendNotificationBalanceChangedEmail($customer) {
        if (!version_compare(Mage::getVersion(), '1.5.0', '>=')) {
            return $this->sendNotificationBalanceChangedEmailOld($customer);
        }   
        
        $storeId = $customer->getStoreId();

        // Retrieve corresponding email template id and customer name        
        $templateId = 'customercredit_email_credit_changed_template';
        if(Mage::getStoreConfig('mageworx_customers/customercredit_email_config/notification_template_balance_changed',$storeId)) {
            $templateId = Mage::getStoreConfig('mageworx_customers/customercredit_email_config/notification_template_balance_changed',$storeId);
        }
        
        $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
        
        $creditData = $customer->getCustomerCreditData();

        if (isset($creditData['value_change'])) $valueChange = floatval($creditData['value_change']); else $valueChange = 0;
        if ($valueChange==0) return $this;
        
        if (isset($creditData['credit_value'])) $creditValue = floatval($creditData['credit_value']); else $creditValue = 0;
        $balance = $creditValue;
        
        if (isset($creditData['comment'])) $comment = trim($creditData['comment']); else $comment = '';        
        
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);
        
        $mailer = Mage::getModel('core/email_template_mailer');        
        
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($customer->getEmail(), $customerName);   
        if(Mage::getStoreConfig('mageworx_customers/customercredit_email_config/enable_bcc')) {
            foreach(explode(',', Mage::getStoreConfig('mageworx_customers/customercredit_email_config/enable_bcc')) as $bcc) {
                $emailInfo->addBcc($bcc, 'Magento Recipient');        
            }
        }
        $mailer->addEmailInfo($emailInfo);

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig('sales_email/order_comment/identity', $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
                'balance'   => $balance,                
                'customerName' => $customerName,
                'comment' => $comment                
            )
        );     
        $translate->setTranslateInline(true);  
       // print_r($customer->getData());         
        $mailer->send();
       
        return $this;
    }        
    
    /**
     * Send email for old magento
     * @param Mage_Customer_Model_Customer $customer
     * @return MageWorx_CustomerCredit_Helper_Data
     */
    public function sendNotificationBalanceChangedEmailOld($customer) {
        // set design parameters, required for email (remember current)
        $currentDesign = Mage::getDesign()->setAllGetOld(array(
            'store'   => $customer->getStoreId(),
            'area'    => 'frontend',
            'package' => Mage::getStoreConfig('design/package/name', $customer->getStoreId()),
        ));

        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $sendTo = array();

        $mailTemplate = Mage::getModel('core/email_template');
        $storeId = $customer->getStoreId();
        $template = 'customercredit_email_credit_changed_template';
        if(Mage::getStoreConfig('mageworx_customers/customercredit_email_config/notification_template_balance_changed',$storeId)) {
            $template = Mage::getStoreConfig('mageworx_customers/customercredit_email_config/notification_template_balance_changed',$storeId);
        }
        $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
        
        $creditData = $customer->getCustomerCreditData();
        if (isset($creditData['value_change'])) $valueChange = floatval($creditData['value_change']); else $valueChange = 0;
        if ($valueChange==0) return $this;
        
        if (isset($creditData['credit_value'])) $creditValue = floatval($creditData['credit_value']); else $creditValue = 0;        
        $balance = $creditValue + $valueChange;        
        
        if (isset($creditData['comment'])) $comment = trim($creditData['comment']); else $comment = '';                

        $sendTo[] = array(
            'name'  => $customerName,
            'email' => $customer->getEmail()
        );        

        foreach ($sendTo as $recipient) {
            $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store' => $customer->getStoreId()))
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig('sales_email/order_comment/identity', $customer->getStoreId()),
                    $recipient['email'],
                    $recipient['name'],
                    array(
                        'balance'   => $balance,                
                        'customerName' => $customerName,
                        'comment' => $comment 
                    )
                );
        }

        $translate->setTranslateInline(true);

        // revert current design
        Mage::getDesign()->setAllGetOld($currentDesign);

        return $this;
    }
    
    /**
     * Send Email
     * @param int $customerId
     * @param inf $daysLeft
     */
    public function sendNotificationExpiration($customerId,$daysLeft) {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $storeId = $customer->getStoreId();
        
        // Retrieve corresponding email template id and customer name        
        $templateId = 'customercredit_email_credit_expiration_notice';    
        if(Mage::getStoreConfig('mageworx_customers/customercredit_email_config/notification_template_expiration_notice',$storeId)) {
            $templateId = Mage::getStoreConfig('mageworx_customers/customercredit_email_config/notification_template_expiration_notice',$storeId);
        }
        $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
        
      
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);
        
        $mailer = Mage::getModel('core/email_template_mailer');        
        
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($customer->getEmail(), $customerName);   
        $mailer->addEmailInfo($emailInfo);

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig('sales_email/order_comment/identity', $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
                'daysLeft'   => $daysLeft,                
                'customerName' => $customerName,
            )
        );
        $translate->setTranslateInline(true);  
        $mailer->send();

        return $this;
    }

    /**
     * Create credit product
     * @return boolean
     */
    public function createCreditProduct() {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();                

        $attributeSetId = $connection->fetchOne("SELECT `default_attribute_set_id` FROM `".$tablePrefix."eav_entity_type` WHERE `entity_type_code` = 'catalog_product'");
        if (!$attributeSetId) return false;               
                
        $productData = array(
            'store_id' => 0,
            'attribute_set_id' => $attributeSetId,
            'type_id' => 'virtual',
            '_edit_mode' => 1,
            'name' => 'Credit Units',
            'sku' => 'customercredit',
            'website_ids' => array_keys(Mage::app()->getWebsites()),
            'status' => 1,
            'tax_class_id' => 0,
            'url_key' => '',
            'visibility' => 1,
            'news_from_date' => '',
            'news_to_date' => '',
            'is_imported' => 0,
            'price' => 1,
            'cost' => '',
            'special_price' => '',
            'special_from_date' => '',
            'special_to_date' => '',
            'enable_googlecheckout' => 1,
            'meta_title' => '',
            'meta_keyword' => '',
            'meta_description' => '', 
            'thumbnail' => 'no_selection',
            'small_image' => 'no_selection',
            'image' => 'no_selection',
            'media_gallery' => Array (
                    'images' => '[]',
                    'values' => '{"thumbnail":null,"small_image":null,"image":null}'
                ),
            'description' => 'This product is used to purchase credit units to fulfill internal balance.',
            'short_description' => 'This product is used to purchase credit units to fulfill internal balance.',
            'custom_design' => '',
            'custom_design_from' => '',
            'custom_design_to' => '',
            'custom_layout_update' => '', 
            'options_container' => 'container2',
            'page_layout' => '',
            'is_recurring' => 0,
            'recurring_profile' => '', 
            'use_config_gift_message_available' => 1,
            'stock_data' => Array (
                    'manage_stock' => 0,
                    'original_inventory_qty' => 0,
                    'qty' => 0,
                    'use_config_min_qty' => 1,
                    'use_config_min_sale_qty' => 1,
                    'use_config_max_sale_qty' => 1,
                    'is_qty_decimal' => 0,
                    'use_config_backorders' => 1,
                    'use_config_notify_stock_qty' => 1,
                    'use_config_enable_qty_increments' => 1,
                    'use_config_qty_increments' => 1,
                    'is_in_stock' => 0,
                    'use_config_manage_stock' => 0                                    
                ),
            'can_save_configurable_attributes' => false,
            'can_save_custom_options' => false,
            'can_save_bundle_selections' => false
        );
                
        try {
            $product = Mage::getModel('catalog/product')->setData($productData)->save();            
            $productId = $product->getId();
            if (version_compare(Mage::getVersion(), '1.5.0', '>=')) {
                Mage::getModel('catalogrule/rule')->applyAllRulesToProduct($productId);
            } else {    
                Mage::getModel('catalogrule/rule')->applyToProduct($productId);
            }
            return $productId;
        } catch (Exception $e) {
            return false;
        }    
    }
    
    /**
     * Get credit product
     * @param boolean $fromConfig
     * @return boolean
     */
    public function getCreditProduct($fromConfig=false) {
        $sku = $this->getCreditProductSku();
        $productId = false;
        if (!$sku) {
            if ($fromConfig) return false;
            $sku = 'customercredit';            
        }    
        $storeId = Mage::app()->getStore()->getId();
        $productId = Mage::getModel('catalog/product')->setStoreId($storeId)->getIdBySku($sku);        
        if (!$productId) return false;
        return Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId);        
    }    
    
    /**
     * Get real credit value
     * @param int $customerId
     * @param int $websiteId
     * @return float
     */
     public function getRealCreditValue($customerId='', $websiteId='') {
       if(!$customerId) {
            $customerId = $this->getCustomerId();
        }
        if(!$websiteId) {
            $storeId = Mage::app()->getRequest()->getParam('store_id',Mage::app()->getStore()->getId());
            $store = Mage::app()->getStore($storeId);
            $websiteId = $store->getWebsiteId();
        }
        $credit = Mage::getModel('customercredit/credit')->setCustomerId($customerId);
        $credit->setWebsiteId($websiteId);
        $creditValue = floatval($credit->loadCredit()->getValue());
                
        if (Mage::app()->getRequest()->getControllerName()=='sales_order_edit' || Mage::app()->getRequest()->getControllerName()=='orderspro_order_edit') {
            $orderId = Mage::getSingleton('adminhtml/session_quote')->getOrderId();
            $orderBaseCustomerCreditAmount = floatval(Mage::getModel('sales/order')->load($orderId)->getBaseCustomerCreditAmount());
            if ($orderBaseCustomerCreditAmount) {
                $creditValue += $orderBaseCustomerCreditAmount;
                Mage::getSingleton('adminhtml/session_quote')->setUseInternalCredit(true);
            }    
        }
        return $creditValue;
    }
    
    /**
     * Get Credit value
     * @param int $customerId
     * @param int $websiteId
     * @return float
     */
    public function getCreditValue($customerId, $websiteId) {
        if($credit = Mage::getSingleton('customer/session')->getCustomCreditValue()) {
            return $credit;
        }
        
        $credit = $this->getRealCreditValue($customerId, $websiteId);
        return $credit;
    }
    
    public function getUsedCreditValue() {
        if (Mage::getSingleton('adminhtml/session_quote')->getCustomerId()) {            
            return Mage::helper('customercredit')->getCreditValue(Mage::getSingleton('adminhtml/session_quote')->getCustomerId(), Mage::app()->getStore(Mage::getSingleton('adminhtml/session_quote')->getStoreId())->getWebsiteId());
        } else {
            return Mage::helper('customercredit')->getCreditValue(Mage::getSingleton('customer/session')->getCustomerId(), Mage::app()->getStore()->getWebsiteId());
        }
    }

        /**
     * Get credit expired date
     * @param int $customerId
     * @param int $websiteId
     * @return int
     */
    public function getCreditExpired($customerId, $websiteId) {
        $today = strtotime(date("Y-m-d"));
        $date = $this->getExpirationTime($customerId, $websiteId);
        
        if($date=='0000-00-00') return false;
        
        $hash = (strtotime($date)-$today)/(3600*24);
        return $hash;
    }
    
    public function getExpirationTime($customerId, $websiteId) {
        $credit = Mage::getModel('customercredit/credit')->setCustomerId($customerId);
        if(!Mage::app()->getStore()->isAdmin()) {
            $credit->setWebsiteId($websiteId);
        }
        $date = $credit->loadCredit()->getExpirationTime();
        return $date;
    }
    
    /**
     * DEPRICATED
     * @param type $customerId
     * @return boolean
     */
    public function checkFirstUseCustomerCredit($customerId) {
        $creditValue = Mage::getModel('customercredit/credit')
                ->setCustomerId($customerId)
                ->loadCredit()
                ->getData('value');
        if (is_null($creditValue)) return false;
        return true;
    }
    
    /**
     * Is can show credits
     * @return boolean
     */
    public function isShowCustomerCredit() {
        if (!is_null($this->_isShowCustomerCreditFlag)) return $this->_isShowCustomerCreditFlag;
        if ($this->isEnabled()) {
           
            $customerId = null;
            if(Mage::app()->getStore()->isAdmin()) {
                //invoice order ?
                $customerId = $this->getCustomerId();
                
                $customer = Mage::getModel('customer/customer')->load($customerId);
                $customerGroupId = $customer->getGroupId();
            } else {
                $customerId = Mage::getSingleton('customer/session')->getCustomerId();
                $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            }
            $avalibleGroupIds = explode(',',Mage::getStoreConfig('mageworx_customers/customercredit_credit/customer_group'));
            if(in_array($customerGroupId,$avalibleGroupIds)) {
                if (Mage::app()->getStore()->isAdmin()) {
                    $this->_isShowCustomerCreditFlag = true;
                    return true;
                } elseif(!$this->isHideCreditUntilFirstUse() || $this->checkFirstUseCustomerCredit($customerId)) {
                    $this->_isShowCustomerCreditFlag = true;
                    return true;
                }
            }
        }
        $this->_isShowCustomerCreditFlag = false;
        return false;
    }
    
    /**
     * Get customer id for all times
     * @return int
     */
    public function getCustomerId() {
        $customerId = null;
        $customer = Mage::getSingleton('customer/session');
        if($customer->isLoggedIn()) {
            $customerId = $customer->getId();
        }
        if(Mage::registry('current_customer')) {
            $customerId = Mage::registry('current_customer')->getId();
        }

        //create order
        if(!$customerId) {
            $customerId = Mage::getSingleton('adminhtml/session_quote')->getCustomerId();
        }

        //refund order
        if(!$customerId AND $orderId = Mage::app()->getRequest()->getParam('order_id')) {
            $order=Mage::getModel('sales/order')->load($orderId);
            $customerId = $order->getCustomerId();
        }
        return $customerId;
    }
    
}