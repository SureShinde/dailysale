<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 *
 * @method Bronto_Reminder_Model_Mysql4_Rule _getResource()
 */
class Bronto_Reminder_Model_Rule
    extends Mage_Rule_Model_Rule
{
    /**
     * Contains data defined per store view, will be used in Messages as variables
     *
     * @var array
     */
    protected $_messageData = array();

    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_reminder/rule');
    }

    /**
     * Perform actions after object load
     *
     * @return Bronto_Reminder_Model_Rule
     */
    protected function _afterLoad()
    {
        Mage_Core_Model_Abstract::_afterLoad();

        if (Mage::helper('bronto_verify')->isVersionMatch(
            Mage::getVersionInfo(),
            1,
            array(array('<=', 6), array('edition' => 'Professional', 'major' => 9), 10, 11))
        ) {
            $conditionsArr = unserialize($this->getConditionsSerialized());
            if (!empty($conditionsArr) && is_array($conditionsArr)) {
                $this->getConditions()->loadArray($conditionsArr);
            }
        }

        $messageData = $this->_getResource()->getMessageData($this->getId());

        foreach ($messageData as $data) {
            $message  = (empty($data['message_id'])) ? null : $data['message_id'];
            $sendType = (empty($data['send_type'])) ? 'transactional' : $data['send_type'];
            $this->setData('store_message_' . $data['store_id'], $message)
                ->setData('store_message_sendtype_' . $data['store_id'], $sendType)
                ->setData('store_message_sendflags_' . $data['store_id'], $data['send_flags']);
        }

        return $this;
    }

    /**
     * Perform actions before object save.
     */
    protected function _beforeSave()
    {
        $this->setConditionSql(
            $this->getConditions()->getConditionsSql(null, new Zend_Db_Expr(':website_id'))
        );

        if (!$this->getSalesruleId()) {
            $this->setSalesruleId(null);
        }
        if (!$this->getProductRecommendationId()) {
            $this->setProductRecommendationId(null);
        }
        parent::_beforeSave();
    }

    /**
     * Live website ids data as is
     *
     * @return Bronto_Reminder_Model_Rule
     */
    protected function _prepareWebsiteIds()
    {
        return $this;
    }

    /**
     * Return conditions instance
     *
     * @return Bronto_Reminder_Model_Rule_Condition_Combine
     */
    public function getConditionsInstance()
    {
        return Mage::getModel('bronto_reminder/rule_condition_combine_root');
    }

    /**
     * Get rule associated website ids
     *
     * @return array
     */
    public function getWebsiteIds()
    {
        if (!$this->hasData('website_ids')) {
            $this->setData('website_ids', $this->_getResource()->getWebsiteIds($this->getId()));
        }

        return $this->_getData('website_ids');
    }

    /**
     * Get array of Registered User abandons and then Guest abandons
     *
     * @param int $limit
     * @param int $threshold (Optional)
     *
     * @return array
     */
    protected function _getRecipients($limit, $threshold = null)
    {
        // Pull in array of customers who abandoned their cart
        return (array)$this->_getResource()->getCustomersForNotification($limit, $this->getRuleId(), $threshold);
    }

    /**
     * Get customer object for recipient
     *
     * @param array                  $recipient
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return boolean|Mage_Customer_Model_Customer
     */
    protected function _getRecipientCustomer(array $recipient, $quote)
    {
        if ($recipient['customer_id'] != 0) {
            /* @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::getModel('customer/customer')->load($recipient['customer_id']);
        } elseif ($quote) {
            // Guest Abandon.  Create Customer on the fly
            $storeId  = $recipient['store_id'];
            $customer = Mage::getModel('customer/customer')
                ->setFirstName($quote->getCustomerFirstname())
                ->setLastName($quote->getCustomerLastname())
                ->setEmail($quote->getCustomerEmail())
                ->setStoreId($storeId)
                ->setId($recipient['customer_id'])
                ->setWebsiteId(Mage::getModel('core/store')->load($storeId)->getWebsiteId());
        }

        if (!$customer || false === $customer->getId()) {
            return false;
        }

        return $customer;
    }

    /**
     * Send reminder emails
     *
     * @param bool $dontSend
     *
     * @return Bronto_Reminder_Model_Rule
     */
    public function sendReminderEmails($dontSend = false)
    {
        // If we aren't matching and we aren't allow to send emails, say so
        if (!$dontSend &&
            !Mage::helper('bronto_reminder')->isAllowSendForAny() ||
            !Mage::helper('bronto_reminder')->isEnabledForAny()
        ) {
            Mage::helper('bronto_reminder')->writeInfo(Mage::helper('bronto_reminder')->getNotAllowedText());

            return $this;
        }

        /* @var $mail Bronto_Reminder_Model_Email_Message */
        $mail      = Mage::getModel('bronto_reminder/email_message');
        $limit     = Mage::helper('bronto_reminder')->getOneRunLimit();
        $identity  = Mage::helper('bronto_reminder')->getEmailIdentity();
        $threshold = Mage::helper('bronto_reminder')->getSendFailureThreshold();

        $this->_matchCustomers();

        if ($dontSend) {
            return $this;
        }

        // Get Array of Recipients
        $recipients = $this->_getRecipients($limit, $threshold);

        $total   = 0;
        $success = 0;
        $error   = 0;
        foreach ($recipients as $recipient) {
            $total++;

            $quote    = false;
            $wishlist = false;

            // Load Store
            /* @var $store Mage_Core_Model_Store */
            $store = Mage::getModel('core/store')->load($recipient['store_id']);

            // If Sending not allowed for this store
            if (!Mage::helper('bronto_reminder')->isAllowSend('store', $store->getId())) {
                $error++;
                continue;
            }

            // Load Quote
            if ($recipient['quote_id'] > 0) {
                /* @var $quote Mage_Sales_Model_Quote */
                $quote = Mage::getModel('sales/quote')
                    ->setStoreId($store->getId())
                    ->loadActive($recipient['quote_id']);
            }

            // Load Wishlist
            if ($recipient['wishlist_id'] > 0) {
                /* @var $wishlist Mage_Wishlist_Model_Wishlist */
                $wishlist = Mage::getModel('wishlist/wishlist')->load($recipient['wishlist_id']);
            }

            // If quote and wishlist are empty, move on to next recipient
            if (false === $quote && false === $wishlist) {
                $error++;
                continue;
            }

            // Load Customer
            /* @var $customer Mage_Customer_Model_Customer */
            if (!$customer = $this->_getRecipientCustomer($recipient, $quote)) {
                $error++;
                continue;
            }

            $messageData = $this->getMessageData($recipient['rule_id'], $store->getId(), $store->getWebsiteId());
            if (!$messageData) {
                Mage::helper('bronto_reminder')->writeInfo("Rule doesn't have an associated Bronto message.");
                $error++;
                continue;
            }

            $coupon = false;
            if (class_exists('Mage_SalesRule_Model_Coupon', false)) {
                /* @var $coupon Mage_SalesRule_Model_Coupon */
                $coupon = Mage::getModel('salesrule/coupon')->load($recipient['coupon_id']);
            }

            $templateVars = array(
                'store'                 => $store,
                'customer'              => $customer,
                'promotion_name'        => $messageData['label'],
                'promotion_description' => $messageData['description'],
                'coupon'                => $coupon,
                'rule'                  => $this,
            );


            if ($quote) {
                $templateVars['quote'] = $quote;
            }
            if ($wishlist) {
                $templateVars['wishlist'] = $wishlist;
            }

            Mage::helper('bronto_reminder')->writeDebug('Sending message to: ' . $customer->getEmail());

            $appEmulation = Mage::getSingleton('core/app_emulation');
            $emulatedInfo = $appEmulation->startEnvironmentEmulation($store->getId());
            try {
                $message = Mage::helper('bronto_reminder/message')->getMessageById(
                    $messageData['message_id'],
                    $store->getId(),
                    $store->getWebsiteId()
                );
                $mail->setTemplateSendType($messageData['send_type']);
                $mail->setSendFlags($messageData['send_flags']);
                $mail->setSalesRule($recipient['coupon_id']);
                $mail->setProductRecommendation($recipient['product_recommendation_id']);
                $mail->sendTransactional(
                    $message,
                    $identity,
                    $customer->getEmail(),
                    null,
                    $templateVars,
                    $store->getId()
                );

            } catch (Exception $e) {
                Mage::helper('bronto_reminder')->writeError('  ' . $e->getMessage());
            }
            $appEmulation->stopEnvironmentEmulation($emulatedInfo);

            if ($mail->getSentSuccess()) {
                Mage::helper('bronto_reminder')->writeDebug('  Success');

                $this->_getResource()
                    ->deactivateMatchedCustomer($recipient['unique_id'])
                    ->addNotificationLog(
                        $recipient['rule_id'],
                        $recipient['unique_id'],
                        $mail->getLastDeliveryId(),
                        $messageData['message_id']
                    );

                $success++;
            } else {
                Mage::helper('bronto_reminder')->writeDebug('  Failed');
                $this->_getResource()->updateFailedEmailsCounter($recipient['rule_id'], $recipient['unique_id']);
                $error++;
            }
        }

        return array(
            'total'   => $total,
            'success' => $success,
            'error'   => $error,
        );
    }

    /**
     * Match customers and assign coupons
     *
     * @return Bronto_Reminder_Model_Observer
     */
    protected function _matchCustomers()
    {
        $threshold   = Mage::helper('bronto_reminder')->getSendFailureThreshold();
        $currentDate = Mage::getModel('core/date')->date('Y-m-d');
        $rules       = $this->getCollection()
            ->addDateFilter($currentDate)
            ->addIsActiveFilter(1);

        if ($ruleId = $this->getRuleId()) {
            $rules->addRuleFilter($ruleId);
        }

        foreach ($rules as $rule) {
            //            $this->_getResource()->deactivateMatchedCustomers($rule->getId());

            if ($rule->getSalesruleId()) {
                /* @var $salesRule Mage_SalesRule_Model_Rule */
                $salesRule  = Mage::getSingleton('salesrule/rule')->load($rule->getSalesruleId());
                $websiteIds = array_intersect($rule->getWebsiteIds(), $salesRule->getWebsiteIds());
            } else {
                $salesRule  = null;
                $websiteIds = $rule->getWebsiteIds();
            }

            $rule->setConditions(null);
            $rule->afterLoad();

            foreach ($websiteIds as $websiteId) {
                $this->_getResource()->saveMatchedCustomers($rule, $salesRule, $websiteId, $threshold);
            }
        }

        return $this;
    }

    /**
     * Return Message data
     *
     * @param int $ruleId
     * @param int $storeId
     *
     * @return array|false
     */
    public function getMessageData($ruleId, $storeId)
    {
        if (!isset($this->_messageData[$ruleId][$storeId])) {
            if ($data = $this->_getResource()->getStoreMessageData($ruleId, $storeId)) {
                if (empty($data['message_id'])) {
                    $data['message_id'] = null;
                }
                $this->_messageData[$ruleId][$storeId] = $data;
            } else {
                return false;
            }
        }

        return $this->_messageData[$ruleId][$storeId];
    }

    /**
     * @param int    $ruleId
     * @param int    $storeId
     * @param int    $customerId
     * @param string $messageId
     *
     * @return boolean|array
     */
    public function getRuleLogItems($ruleId, $storeId, $customerId, $messageId = null)
    {
        if ($data = $this->_getResource()->getRuleLogItemsData($ruleId, $storeId, $customerId, $messageId)) {
            return $data;
        }

        return false;
    }

    /**
     * Remove row from coupon table by column, value and store_id
     *
     * @param $column
     * @param $value
     */
    public function removeFromReminders($column, $value)
    {
        $this->_getResource()->removeFromReminders($column, $value);
    }
}
