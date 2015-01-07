<?php

/**
 * @package       Bronto/Common
 * @copyright (c) 2011-2012, Bronto Software, Inc.
 */
class Bronto_Common_Model_Email_Template extends Mage_Core_Model_Email_Template
{
    /**
     * @var string
     */
    protected $_helper = 'bronto_common';

    /**
     * @var Bronto_Api_Message_Row
     */
    protected $_message;

    /**
     * @var string
     */
    protected $_lastDeliveryId;

    /**
     * @var Bronto_Email_Model_Log
     */
    protected $_log;

    /**
     * Set the message
     *
     * @param Bronto_Api_Message_Row $message
     */
    public function setMessage(Bronto_Api_Message_Row $message)
    {
        $this->_message = $message;
    }

    /**
     * Get the message currently set
     *
     * @return boolean|Bronto_Api_Message_Row False if no message is set
     */
    public function getMessage()
    {
        if (empty($this->_message)) {
            $messageId = $this->getBrontoMessageId();
            if (!empty($messageId)) {
                $this->_message = Mage::helper('bronto_common/message')->getMessageById($messageId);
            } else {
                return false;
            }
        }

        return $this->_message;
    }

    /**
     * Get filter object for template processing logic
     *
     * @param null $storeId
     *
     * @return false|Mage_Core_Model_Abstract|Mage_Core_Model_Email_Template_Filter
     */
    public function getTemplateFilter($storeId = null)
    {
        if (!Mage::helper($this->_helper)->canSendBronto($this, $storeId)) {
            return parent::getTemplateFilter();
        }

        if (empty($this->_templateFilter)) {
            $this->_templateFilter = Mage::getModel('bronto_common/email_template_filter');
        }

        return $this->_templateFilter;
    }

    /**
     * Gets the the global recommendation or the one configured for the email
     *
     * @param int $storeId
     * @return int
     */
    protected function _getRecommendationId($storeId = null)
    {
        $recommendationId = $this->getProductRecommendation();
        if ($recommendationId) {
            return $recommendationId;
        }

        if ($this->_helper == 'bronto_email') {
            $recommendationId = Mage::helper('bronto_email')->getDefaultRecommendation('store', $storeId);
        }

        return $recommendationId;
    }

    /**
     * Gets the global rule id or the one configured for the email
     *
     * @param int $storeId
     * @return int
     */
    protected function _getSalesRule($storeId = null)
    {
        $rule = Mage::getModel('salesrule/rule');
        $ruleId = $this->getSalesRule();
        if ($ruleId) {
            if ($rule->load($ruleId)->getId()) {
                return $rule;
            }
        }

        if ($this->_helper == 'bronto_email') {
            $ruleId = Mage::helper('bronto_email')->getDefaultRule('store', $storeId);
            if ($ruleId) {
                if ($rule->load($ruleId)->getId()) {
                    return $rule;
                }
            }
        }

        return null;
    }

    /**
     * Gets the delivery flags for this message
     *
     * @param int $storeId
     * @return int
     */
    protected function _getSendFlags($storeId = null)
    {
        $sendFlags = $this->getSendFlags();
        if ($sendFlags) {
            return $sendFlags;
        }

        if ($this->_helper == 'bronto_email') {
            $sendFlags = Mage::helper('bronto_email')->getDefaultSendFlags('store', $storeId);
        }
        return $sendFlags;
    }

    /**
     * Process email template code
     *
     * @param Bronto_Api_Delivery_Row $delivery
     * @param array                   $variables
     *
     * @return Bronto_Api_Delivery_Row
     */
    public function getProcessedDelivery(Bronto_Api_Delivery_Row $delivery, array $variables = array())
    {
        $processor = $this->getTemplateFilter($variables['store']->getId());

        if (isset($variables['subscriber']) && ($variables['subscriber'] instanceof Mage_Newsletter_Model_Subscriber)) {
            $processor->setStoreId($variables['subscriber']->getStoreId());
        }

        if ($this->getBrontoMessageId()) {
            $processor->setMessageId($this->getBrontoMessageId());
        }

        $processor->setVariables($variables);
        $processor->setAvailable($this->getVariablesOptionArray());

        return $processor->filter($delivery);
    }

    /**
     * If this message can be used for sending queue as main template
     *
     * @return boolean
     */
    public function isMessageValidForSend()
    {
        if (!($this->getSenderName() && $this->getSenderEmail())) {
            Mage::helper($this->_helper)->writeError('  Message cannot be sent: Sender Name or Email is not set');

            return false;
        }

        return true;
    }

    /**
     * TODO: move this into an event handler of some kind
     * Gets any related fields for a delivery
     *
     * @param Bronto_Common_Model_Queue $queue
     */
    protected function _setRelatedFields($queue)
    {
        $helper = Mage::helper('bronto_product');
        $info = $queue->getRecommendationInfo();
        if ($info->getId() && $helper->isEnabled('store', $queue->getStoreId())) {
            $recommendation = Mage::getModel('bronto_product/recommendation')
                ->load($info->getId());
            if ($recommendation->hasEntityId()) {
                $data = $queue->getUnserializedEmailData()->getData();
                if ($info->getCustomer()) {
                    $customer = Mage::getModel('customer/customer')->setId($info->getCustomer());
                    $recommendation->setCustomer($customer);
                }
                $hash = $helper->collectRecommendations(
                    $recommendation,
                    $queue->getStoreId(),
                    $info->getProductIds());
                $fields = $helper->relatedFields($hash, $queue->getStoreId());
                foreach ($fields as $relatedFields) {
                    $data['delivery']['fields'] = array_merge($data['delivery']['fields'], $relatedFields);
                }
                $queue->setUnserializedEmailData($data);
            }
        }
    }

    /**
     * Sends a Bronto Delivery
     *
     * @param Bronto_Common_Model_Queue $queue
     * @return boolean
     */
    public function sendDeliveryFromQueue($queue)
    {
        $deliveryErrors = 0;
        $message = $queue->getMessage();
        $contacts = $queue->getContacts();

        $this->setSendQueue($queue);
        $this->setMessage($message);
        $this->setBrontoMessageId($message->id);
        $this->setBrontoMessageName($message->name);
        $this->setBrontoMessageApproved(1);
        $this->_setRelatedFields($queue);

        foreach ($contacts as $contact) {
            $this->_beforeSend($contact, $message);
            if (!$contact->id) {
                $deliveryErrors++;
                $this->_afterSend(false, "{$contact->email}: {$contact->error}");
                continue;
            }
            try {
                $delivery = $queue->prepareDelivery();
                $delivery->start = $this->_startTime($queue->getStoreId());
                $recipients = array(
                    array(
                        'type' => 'contact',
                        'id'   => $contact->id,
                        'deliveryType' => 'selected'
                    ),
                );
                $list = Mage::getModel('bronto_common/list', array(
                    $this->_helper,
                    $queue->getAdditionalData()->getExclusionList()
                ));
                $excludes = $list->addAdditionalRecipients($queue->getStoreId());
                foreach ($excludes as $exclude) {
                      $recipients[] = $exclude;
                }
                $delivery->recipients = $recipients;
                $delivery->save();

                if ($delivery->id) {
                    $this->setLastDeliveryId($delivery->id);
                    $this->_afterSend(true, null, $delivery);
                } else {
                    $this->_afterSend(false, null, $delivery);
                }
            } catch (Exception $e) {
                $deliveryErrors++;
                $errorMessage = $e->getMessage();
                if ($e->getCode() === Bronto_Api_Delivery_Exception::MESSAGE_NOT_TRANSACTIONAL_APPROVED) {
                    // Replace message id with message name
                    if (preg_match_all("/([a-zA-Z0-9\-]){36}/", $errorMessage, $matches)) { // Grab field id if exists
                        foreach ($matches[0] as $match) {
                            $errorMessage = str_replace($match, $message->name, $errorMessage);
                        }
                    }
                }

                Mage::helper($this->_helper)->writeError($errorMessage);
                $this->_afterSend(false, $errorMessage, $delivery);
            }
        }
        return $deliveryErrors == 0;
    }

    /**
     * Send mail to recipient
     *
     * @param array|string      $email     E-mail(s)
     * @param array|string|null $name      receiver name(s)
     * @param array             $variables template variables
     *
     * @return boolean
     */
    public function send($email, $name = null, array $variables = array())
    {
        // In the rare case we got here w/o hitting sendTransactional first...
        if (!isset($variables['store']) || !is_object($variables['store'])) {
            // Get the current store view, as to not break things
            $variables['store'] = Mage::app()->getStore();
        }

        // If not set to go through Bronto, fall through to magento sending
        if (!Mage::helper($this->_helper)->canSendBronto($this, $variables['store']->getId())) {
            return parent::send($email, $name, $variables);
        }

        /* @var $message Bronto_Api_Message_Row */
        $messageId = $this->getBrontoMessageId();
        $sendType = $this->getTemplateSendType();
        if ($sendType == 'marketing') {
            $sendType = 'triggered';
        }

        // If messageId is empty, send through Magento
        if (empty($messageId)) {
            return parent::send($email, $name, $variables);
        }

        // If message is not valid for sending, return false
        if (!$this->isMessageValidForSend()) {
            return false;
        }

        // Handle CC and BCC by simply adding as array
        $emails = array_values((array)$email);
        $names  = is_array($name) ? $name : (array)$name;
        $names  = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        $variables['email'] = reset($emails);
        $variables['name']  = reset($names);

        $apiHelper = Mage::helper('bronto_common/api');
        $api = $apiHelper->getApi(null, 'store', $variables['store']->getId());

        $delivery = $api->getDeliveryObject()->createRow();
        $delivery = $this->getProcessedDelivery($delivery, $variables);
        $this->_additionalFields($delivery, $variables);
        $productContext = Mage::helper('bronto_product')
            ->itemsToProductHash($this->getTemplateFilter()
            ->getProductContext(), $variables['store']->getId());
        $sendOptions = Mage::getModel('bronto_common/system_config_source_sendOptions');
        $sendOption = $this->_getSendFlags($variables['store']->getId());
        if ($sendOptions->setDeliveryFlags($delivery, $sendOption)) {
            $optionLabels = $sendOptions->toArray();
            Mage::helper($this->_helper)->writeDebug("Setting send flags for messageId {$this->getBrontoMessageId()}: {$optionLabels[$sendOption]}");
        }

        $data = array(
            'emails' => $emails,
            'params' => $this->_additionalData(),
            'recommendation' => array(
                'product_ids' => array_keys($productContext),
                'customer' => $this->getTemplateFilter()->getCustomerId(),
                'id' => $this->_getRecommendationId($variables['store']->getId()),
            ),
            'delivery' => array(
                'messageId' => $messageId,
                'type' => $sendType ? $sendType : 'transactional',
                'fromEmail' => $this->getSenderEmail(),
                'fromName' => $this->getSenderName(),
                'replyEmail' => $this->getSenderEmail(),
            ) + $delivery->getData()
        );

        $queue = Mage::getModel('bronto_common/queue')
            ->setStoreId($variables['store']->getId())
            ->setApi($api)
            ->setUnserializedEmailData($data)
            ->setEmailClass($this->_emailClass())
            ->setCreatedAt(Mage::getSingleton('core/date')->gmtDate());

        if ($this->_queuable() && $apiHelper->canUseQueue('store', $queue->getStoreId())) {
            try {
                $queue->save();
                return true;
            } catch (Exception $e) {
                $apiHelper->writeError('Failed to save email to queue for store ' . $queue->getStoreId() . ': ' . $e->getMessage());
                return $this->sendDeliveryFromQueue($queue);
            }
        } else {
            return $this->sendDeliveryFromQueue($queue);
        }
    }

    /**
     * Send transactional email to recipient
     *
     * @param int          $templateId
     * @param string|array $sender Sender information, can be declared as part of config path
     * @param string       $email  Recipient email
     * @param string       $name   Recipient name
     * @param array        $vars   Variables which can be used in template
     * @param int|null     $storeId
     *
     * @return Mage_Core_Model_Email_Template
     */
    public function sendTransactional($templateId, $sender, $email, $name, $vars = array(), $storeId = null)
    {
        // If Template ID is 'nosend', then simply return false
        if ($templateId == 'nosend') {
            return false;
        }

        // If not set to go through Bronto, fall through to magento sending
        if (!Mage::helper($this->_helper)->canSendBronto($this, $storeId)) {
            return parent::sendTransactional($templateId, $sender, $email, $name, $vars, $storeId);
        } else {
            // If module enabled and template ID is not an instance of the api row, see if we can pull an instance
            if (!($templateId instanceof Bronto_Api_Message_Row)) {
                $emailTemplate = Mage::getModel('bronto_email/template')
                    ->setDesignConfig($this->getDesignConfig()->getData());

                // If $templateId is numeric, load template by ID, else is default and should send through Magento
                if (is_numeric($templateId)) {
                    $emailTemplate->load($templateId);
                } else {
                    $this->setTemplateSendType('magento');
                    return parent::sendTransactional($templateId, $sender, $email, $name, $vars, $storeId);
                }

                // If Template doesn't have a Bronto Message ID, send through magento
                if (!$emailTemplate->getBrontoMessageId() || is_null($emailTemplate->getBrontoMessageId())) {
                    return parent::sendTransactional($templateId, $sender, $email, $name, $vars, $storeId);
                }

                $message = new Bronto_Api_Message_Row();
                $message->id = $emailTemplate->getBrontoMessageId();

                // Send through main template model
                $emailTemplate->sendTransactional(
                    $message,
                    $sender,
                    $email,
                    $name,
                    $vars,
                    $storeId
                );

                return $this->setSentSuccess($emailTemplate->getSentSuccess());
            } else {
                $this->setBrontoMessageId($templateId->id);
            }
        }

        // Start the send process
        $this->setSentSuccess(false);
        if (($storeId === null) && $this->getDesignConfig()->getStore()) {
            $storeId = $this->getDesignConfig()->getStore();
        }

        // If $sender is not array, it is a reference to a config setting, otherwise it should have 'name' and 'email'
        if (!is_array($sender)) {
            $this->setSenderName(Mage::getStoreConfig('trans_email/ident_' . $sender . '/name', $storeId));
            $this->setSenderEmail(Mage::getStoreConfig('trans_email/ident_' . $sender . '/email', $storeId));
        } else {
            $this->setSenderName($sender['name']);
            $this->setSenderEmail($sender['email']);
        }

        // If store is not set, set it
        if (!isset($vars['store'])) {
            $vars['store'] = Mage::app()->getStore($storeId);
        }

        // Check for Sales Rules
        if (!isset($vars['coupon']) && $rule = $this->_getSalesRule($storeId)) {
            try {
                /** @var Mage_SalesRule_Model_Coupon $coupon */
                $coupon = $rule->acquireCoupon();

                if ($coupon) {
                    $vars['coupon'] = $coupon;
                }
            } catch (Exception $e) {
                Mage::helper($this->_helper)->writeError('  Failed loading Sales Rule with ID: ' . $rule->getId());
            }
        }

        // Send
        $this->setSentSuccess($this->send($email, $name, $vars));

        return $this;
    }

    /**
     * @param string $deliveryId
     *
     * @return Bronto_Common_Model_Email_Template
     */
    public function setLastDeliveryId($deliveryId)
    {
        $this->_lastDeliveryId = $deliveryId;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastDeliveryId()
    {
        return $this->_lastDeliveryId;
    }

    /**
     * Allows the retryer to trigger
     *
     * @param Bronto_Api_Delivery $deliveryObject
     */
    public function triggerBeforeAfterSend(Bronto_Api_Delivery_Row $delivery)
    {
        $contactObject = $delivery->getApi()->getContactObject();
        $messageObject = $delivery->getApi()->getMessageObject();

        $contact = $contactObject->createRow();
        $message = $messageObject->createRow();

        $message->id = $delivery->messageId;
        $contact->id = $delivery->recipients[0]['id'];

        try {
            $message->read();
            $contact->read();

            $this->_beforeSend($contact, $message);
            $this->_afterSend(true, null, $delivery);
        } catch (Exception $e) {
            Mage::helper($this->_helper)->writeError('Failed to trigger email send: ' . $e->getMessage());
        }
    }

    /**
     * The string model class associating this email template
     *
     * @return string
     */
    protected function _emailClass()
    {
        return 'bronto_common/email_template';
    }

    /**
     * Is this a queuable message?
     *
     * @return bool
     */
    protected function _queuable()
    {
        return true;
    }

    /**
     * When should this email be sent out?
     *
     * @param int $storeId
     * @return string
     */
    protected function _startTime($storeId)
    {
        return date('c');
    }

    /**
     * Ability to set additional fields
     *
     * @param $delivery
     * @param $variables
     */
    protected function _additionalFields($delivery, $variables)
    {
    }

    /**
     * Ability to add to the serialization data
     *
     * @return array
     */
    protected function _additionalData()
    {
        return array();
    }

    /**
     * @param Bronto_Api_Contact_Row $contact
     * @param Bronto_Api_Message_Row $message
     */
    protected function _beforeSend(Bronto_Api_Contact_Row $contact, Bronto_Api_Message_Row $message)
    {
    }

    /**
     * @param int                     $success
     * @param string                  $error
     * @param Bronto_Api_Delivery_Row $delivery
     */
    protected function _afterSend($success, $error = null, Bronto_Api_Delivery_Row $delivery = null)
    {
    }
}
