<?php

class TM_NewsletterBooster_Model_Queue extends Mage_Newsletter_Model_Queue
{
    /**
     * Newsletter Template object
     *
     * @var Mage_Newsletter_Model_Template
     */
    protected $_template;

    /**
     * Subscribers collection
     * @var Varien_Data_Collection_Db
     */
    protected $_subscribersCollection = null;

    /**
     * save template flag
     *
     * @var boolean
     * @deprecated since 1.4.0.1
     */
    protected $_saveTemplateFlag = false;

    /**
     * Save stores flag.
     *
     * @var boolean
     */
    protected $_saveStoresFlag = false;

    /**
     * Stores assigned to queue.
     *
     * @var array
     */
    protected $_stores = array();

    const STATUS_NEVER = 0;
    const STATUS_SENDING = 1;
    const STATUS_CANCEL = 2;
    const STATUS_SENT = 3;
    const STATUS_PAUSE = 4;

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('newsletterbooster/queue');
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();

        $serializeData = unserialize($this->getData('campaign_serialize'));
        $this->addData($serializeData);
    }

    /**
     * Set $_data['queue_start'] based on string from backend, which based on locale.
     *
     * @param string|null $startAt start date of the mailing queue
     * @return Mage_Newsletter_Model_Queue
     */
    public function setQueueStartAtByString($startAt)
    {
        if(is_null($startAt) || $startAt == '') {
            $this->setQueueStartAt(null);
        } else {
            $locale = Mage::app()->getLocale();
            $format = $locale->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
            $time = $locale->date($startAt, $format)->getTimestamp();
            $this->setQueueStartAt(Mage::getModel('core/date')->gmtDate(null, $time));
        }
        return $this;
     }

    /**
     * Send messages to subscribers for this queue
     *
     * @param   int     $count
     * @param   array   $additionalVariables
     * @return Mage_Newsletter_Model_Queue
     */
    public function sendPerSubscriber($count=20, array $additionalVariables=array())
    {
        if($this->getQueueStatus()!=self::STATUS_SENDING
           && ($this->getQueueStatus()!=self::STATUS_NEVER && $this->getQueueStartAt())
        ) {
            return $this;
        }

        $indexModel = Mage::getModel('segmentationsuite/index');
        $sendModel = Mage::getModel('newsletterbooster/send');
        $unserializeData = unserialize($this->getCampaignSerialize());

        if ($this->getQueueStatus() == self::STATUS_NEVER) {
            $this->setQueueStatus(self::STATUS_SENDING);
            $this->setRecipients($indexModel->getRecipientsCount($unserializeData['tm_segment'], $unserializeData['campaign_id'], $this->getId(),$unserializeData['sent_guest']));
            $this->save();
        }

        /* @var $sender Mage_Core_Model_Email_Template */
        $sender = Mage::getModel('newsletterbooster/campaign');
        $sender->setTemplateSenderName($unserializeData['template_sender_name'])
            ->setTemplateSenderEmail($unserializeData['template_sender_email'])
            ->setTemplateType($unserializeData['template_type'])
            ->setTemplateSubject($unserializeData['template_subject'])
            ->setTemplateText($unserializeData['template_text'])
            ->setTemplateStyles($unserializeData['template_styles'])
            ->setTmGateway($unserializeData['tm_gateway'])
            ->setTrackOpens($unserializeData['track_opens'])
            ->setTrackClicks($unserializeData['track_clicks'])
            ->setQueueId($this->getId())
            ->setCampaignId($unserializeData['campaign_id']);
            //->setTemplateFilter(Mage::helper('newsletter')->getTemplateProcessor());

        $emails = $indexModel->getEmailsToSend($unserializeData['tm_segment'], $this->getQueueId(), $count, $this->getProcessed(), $unserializeData['campaign_id']);
        $emails = array_unique($emails);
        /*  aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa   */
        $customer = Mage::getModel('customer/customer');
        $subscribers = Mage::getModel('newsletterbooster/subscriber');
        $unsubscribe = Mage::getModel('newsletterbooster/unsubscribe');
        foreach($emails as $email) {
            $this->setProcessed($this->getProcessed() + 1);
            $item = $customer->load($email);
                $item->setStoreId(1);
            if ($subscribers->subscribeExist($unserializeData['campaign_id'], null, $item->getEmail()) ||
            $unsubscribe->unsubscribeExist($unserializeData['campaign_id'], null, null, $item->getEmail())) {
                $this->setRecipients($this->getRecipients() - 1);
                $this->save();
            } else {
                $sender->setCustomerId($email);
                $sender->emulateDesign($item->getStoreId());
                $successSend = $sender->sendTestMail($item->getEmail(), $item->getName(), array('customer' => $item, 'campaign' => $sender));
                $sender->revertDesign();
                $this->save();

                if($successSend) {
                    $sendModel->setId(null);
                    $sendModel->setQueueId($this->getId())
                        ->setCustomerId($item->getId());
                    $sendModel->save();

                } else {
                    $this->setErrors($this->getErrors() + 1);
                    $this->save();
                }
            }
        }

        if($this->getRecipients() ==  $this->getProcessed() + $this->getGuest()) {
            $this->_finishQueue();
        }
        return $this;
    }

    public function sendPerGuest($count=20, array $additionalVariables=array())
    {
        if($this->getQueueStatus()!=self::STATUS_SENDING
           && ($this->getQueueStatus()!=self::STATUS_NEVER && $this->getQueueStartAt())
        ) {
            return $this;
        }

        $subscribeModel = Mage::getModel('newsletterbooster/subscriber');
        $sendModel = Mage::getModel('newsletterbooster/send');
        $unserializeData = unserialize($this->getCampaignSerialize());
        if (!$unserializeData['sent_guest']) {
            return $this;
        }
        /* @var $sender Mage_Core_Model_Email_Template */
        $sender = Mage::getModel('newsletterbooster/campaign');
        $sender->setTemplateSenderName($unserializeData['template_sender_name'])
            ->setTemplateSenderEmail($unserializeData['template_sender_email'])
            ->setTemplateType($unserializeData['template_type'])
            ->setTemplateSubject($unserializeData['template_subject'])
            ->setTemplateText($unserializeData['template_text'])
            ->setTemplateStyles($unserializeData['template_styles'])
            ->setTmGateway($unserializeData['tm_gateway'])
            ->setTrackOpens($unserializeData['track_opens'])
            ->setTrackClicks($unserializeData['track_clicks'])
            ->setQueueId($this->getId())
            ->setCampaignId($unserializeData['campaign_id']);
            //->setTemplateFilter(Mage::helper('newsletter')->getTemplateProcessor());

        $emails = $subscribeModel->getEntityForSend($count, $this->getGuest(), $unserializeData['campaign_id']);
        $emails = array_unique($emails);

        if ($this->getQueueStatus() == self::STATUS_NEVER) {
            $this->setQueueStatus(self::STATUS_SENDING);
            $this->setRecipients(count($emails));
            $this->save();
        }
        //$emails = array_unique($emails);
        /*  aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa   */
        $customer = Mage::getModel('customer/customer');

        foreach($emails as $data) {
            if ('0' == $data['entity_id']) {
                $customer->setId(null)
                    ->setName($data['firstname'] . ' ' . $data['lastname'])
                    ->setEmail($data['email'])
                    ->setFirstname($data['firstname'])
                    ->setLastname($data['lastname'])
                    ->setStoreId($data['store']);
                $item = $customer;
            } else {
                $item = $customer->load($data['entity_id']);
            }

            $sender->setCustomerId($data['entity_id']);
            $sender->emulateDesign($item->getStoreId());
            $successSend = $sender->sendTestMail($item->getEmail(), $item->getName(), array('customer' => $item, 'campaign' => $sender));
            $sender->revertDesign();
            $this->setGuest($this->getGuest() + 1);
            $this->save();

            if($successSend) {
                $sendModel->setId(null);
                $sendModel->setQueueId($this->getId())
                    ->setCustomerId($item->getId());
                $sendModel->save();

            } else {
                $this->setErrors($this->getErrors() + 1);
                $this->save();
            }
        }

        if($this->getRecipients() ==  $this->getProcessed() + $this->getGuest()) {
            $this->_finishQueue();
        }
        return $this;
    }

    /**
     * Finish queue: set status SENT and update finish date
     *
     * @return Mage_Newsletter_Model_Queue
     */
    protected function _finishQueue()
    {
        $this->setQueueFinishAt(Mage::getSingleton('core/date')->gmtDate());
        $this->setQueueStatus(self::STATUS_SENT);
        $this->save();

        return $this;
    }

    /**
     * Getter data for saving
     *
     * @return array
     */
    public function getDataForSave()
    {
        $data = array();
        $data['template_id'] = $this->getTemplateId();
        $data['queue_status'] = $this->getQueueStatus();
        $data['queue_start_at'] = $this->getQueueStartAt();
        $data['queue_finish_at'] = $this->getQueueFinishAt();
        return $data;
    }

    /**
     * Add subscribers to queue.
     *
     * @param array $subscriberIds
     * @return Mage_Newsletter_Model_Queue
     */
    public function addSubscribersToQueue(array $subscriberIds)
    {
        $this->_getResource()->addSubscribersToQueue($this, $subscriberIds);
        return $this;
    }

    public function isPlain()
    {
        return $this->getType() == TM_NewsletterBooster_Model_Queue::TYPE_TEXT;
    }

    public function getTotalQueueCount($storeId, $campaignId)
    {
        return $this->_getResource()->getTotalQueueCount($storeId, $campaignId);
    }

    public function getOpensCount($queueId)
    {
        return $this->_getResource()->getOpensCount($queueId);
    }

    public function getClicksCount($queueId)
    {
        return $this->_getResource()->getClicksCount($queueId);
    }

    public function getCountryOpensData($queueId)
    {
        return $this->_getResource()->getCountryOpensData($queueId);
    }

    public function getCampaignCountryOpensData($campaign)
    {
        return $this->_getResource()->getCampaignCountryOpensData($campaign);
    }

    public function getRegionOpensData($queueId)
    {
        return $this->_getResource()->getRegionOpensData($queueId);
    }

    public function getCampaignRegionOpensData($campaign)
    {
        return $this->_getResource()->getCampaignRegionOpensData($campaign);
    }

    public function getUnsubscribeCount($queueId)
    {
        return $this->_getResource()->getUnsubscribeCount($queueId);
    }

    public function getCampaignRecipientsCount($campaignId)
    {
        return $this->_getResource()->getCampaignRecipientsCount($campaignId);
    }

    public function getCampaignOpensCount($campaignId)
    {
        return $this->_getResource()->getCampaignOpensCount($campaignId);
    }

    public function getCampaignClicksCount($campaignId)
    {
        return $this->_getResource()->getCampaignClicksCount($campaignId);
    }

    public function getCampaignQueueProcessed($campaignId)
    {
        return $this->_getResource()->getCampaignQueueProcessed($campaignId);
    }

    public function getCampaignUnsubscribeCount($campaignId)
    {
        return $this->_getResource()->getCampaignUnsubscribeCount($campaignId);
    }

    public function getCampaignQueueErrors($campaignId)
    {
        return $this->_getResource()->getCampaignQueueErrors($campaignId);
    }

    public function getSendedQueue($campaignId)
    {
        return $this->_getResource()->getSendedQueue($campaignId);
    }
}
