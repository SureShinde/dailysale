<?php

/**
 * @package   Bronto\Newsletter
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Newsletter_Model_Observer
    extends Mage_Core_Model_Abstract
{

    const NOTICE_IDENTIFIER = 'bronto_newsletter';
    const BOX_UNCHECKED     = 0;
    const BOX_CHECKED       = 1;
    const BOX_NOT_CHANGED   = 2;

    private $_helper;

    public function __construct()
    {
        /* @var $_helper Bronto_Newsletter_Helper_Data */
        $this->_helper = Mage::helper(self::NOTICE_IDENTIFIER);
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return mixed
     */
    public function checkBrontoRequirements(Varien_Event_Observer $observer)
    {
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            return;
        }

        // Verify Requirements
        if (!$this->_helper->varifyRequirements(self::NOTICE_IDENTIFIER, array('soap', 'openssl'))) {
            return;
        }
    }

    /**
     * Observes module becoming enabled and displays message warning user to configure settings
     * @param Varien_Event_Observer $observer
     */
    public function watchEnableAction(Varien_Event_Observer $observer)
    {
        Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('bronto_newsletter')->__(Mage::helper('bronto_newsletter')->getModuleEnabledText()));
    }

    /**
     * Get Bronto Contact Row via Email address
     *
     * @param string $email
     *
     * @return boolean|Bronto_Api_Contact_Row
     */
    protected function _getBrontoContact($email)
    {
        try {
            /* @var $contact Bronto_Api_Contact_Row */
            $contact = Mage::helper('bronto_newsletter/contact')->getContactByEmail(
                $email,
                null,
                Mage::app()->getStore()->getId()
            );

            return $contact;
        } catch (Exception $e) {
            $this->_helper->writeError($e);

            return false;
        }
    }

    /**
     * Observe checkout event and handle assigning status
     *
     * @param Varien_Event_Observer $observer
     *
     * @return boolean|Varien_Event_Observer
     */
    public function handleSubscriptionAtCheckout(Varien_Event_Observer $observer)
    {
        if (!$this->_helper->isEnabled()) {
            return false;
        }

        // Get Subscription status from session
        $isSubscribed = Mage::getSingleton('checkout/session')->getIsSubscribed();
        if (empty($isSubscribed)) {
            $this->_helper->writeDebug('No subscription status found in session.');
            return false;
        }

        try {
            // Get e-mail address we are working with
            $email = $observer->getEvent()->getOrder()->getData('customer_email');

            if (empty($email)) {
                $this->_helper->writeError('No customer email was provided.');

                return false;
            }

            /* @var $subscriber Mage_Newsletter_Model_Subscriber */
            $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
            if (!$subscriber->hasSubscriberEmail() && $isSubscribed == Bronto_Api_Contact::STATUS_TRANSACTIONAL) {
                $this->_helper->writeDebug('Unable to create subscriber object');

                return false;
            }

            /* @var $contact Bronto_Api_Contact_Row */
            if (!$contact = $this->_getBrontoContact($email)) {
                $this->_helper->writeError('Unable to create contact object');

                return false;
            }

            // Determine action
            switch ($isSubscribed) {
                case Bronto_Api_Contact::STATUS_ACTIVE:
                case Bronto_Api_Contact::STATUS_ONBOARDING:
                    if ($subscriber->getStatus() != Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                        $subscriber->subscribe($email);
                    }
                    break;
                case Bronto_Api_Contact::STATUS_UNSUBSCRIBED:
                    return $subscriber->unsubscribe();
                    break;
                case Bronto_Api_Contact::STATUS_TRANSACTIONAL:
                default:
                    $this->_makeTransactional($subscriber, $email);
                    break;
            }
        } catch (Exception $e) {
            $this->_helper->writeError($e);
        }

        return $observer;
    }

    /**
     * Handle setting subscriber as transactional in bronto queue and
     * removing from magento subscription
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param string                           $email
     *
     * @return boolean|Mage_Newsletter_Model_Subscriber
     */
    private function _makeTransactional(Mage_Newsletter_Model_Subscriber $subscriber, $email)
    {
        /* @var $contact Bronto_Api_Contact_Row */
        if (!$contact = $this->_getBrontoContact($email)) {
            $this->_helper->writeError('Unable to create contact object');

            return false;
        }

        // Get Customer using the email provided
        $customerId = Mage::getModel('customer/customer')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
            ->loadByEmail($email)
            ->getId();

        if (!$customerId) {
            $customerId = Mage::getSingleton('customer/session')->getId();
        }

        // Set Magento Subscriber and Status
        $subscriber->setCustomerId($customerId)
            ->setSubscriberEmail($email)
            ->setStoreId(Mage::app()->getStore()->getId())
            ->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE)
            ->save();

        if ($contact->status == Bronto_Api_Contact::STATUS_UNSUBSCRIBED) {
            $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
        } else {
            $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE);
        }

        $subscriber->save();

        return $subscriber;
    }

    /**
     * Update Bronto from Magento Subscriber Status
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this|bool
     */
    public function updateBrontoFromNewsletterStatus(Varien_Event_Observer $observer)
    {
        if (!$this->_helper->isEnabled()) {
            return false;
        }

        // Insert contact email into queuing table. Cron will
        // then issue an update to Bronto on its next run.
        try {
            /* @var $subscriber Mage_Newsletter_Model_Subscriber */
            if (!$subscriber = $observer->getEvent()->getSubscriber()) {
                $this->_helper->writeError('Unable to create subscriber object');

                return false;
            }

            // Send to queue
            $this->_saveToQueue($subscriber, $subscriber->getStoreId());
        } catch (Exception $e) {
            $this->_helper->writeError($e);
        }

        return $this;
    }

    /**
     * Add Subscriber to Bronto Newsletter Opt-in queue
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param int                              $storeId
     *
     * @return $this|bool
     */
    private function _saveToQueue(Mage_Newsletter_Model_Subscriber $subscriber, $storeId)
    {
        // Get e-mail address we are working with
        $email = $subscriber->getEmail();
        if (empty($email)) {
            $this->_helper->writeError('Subscriber does not have an email address.');

            return false;
        }

        // Get Calculated Status
        $status = Mage::helper('bronto_newsletter/contact')->getQueueStatus($subscriber);

        /* @var $contactQueue Bronto_Newsletter_Model_Queue */
        $contactQueue = Mage::getModel('bronto_newsletter/queue')
            ->getContactRow($subscriber->getId(), $storeId);

        // If ContactQueue status doesn't match subscriber status, replace it
        if ($status != $contactQueue->getStatus()) {
            if ($subscriber->getSubscribeSource() == 'popup') {
                $contactQueue->setImported(2);
            } else {
                $contactQueue->setImported(0);
            }
            $contactQueue->setSubscriberEmail($subscriber->getEmail())
                ->setStatus($status)
                ->setMessagePreference('html')
                ->setSource('api')
                ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate())
                ->save();
        }

        return $this;
    }

    /**
     * Process all queued subscribers for the specified store and import them into Bronto
     *
     * @param string|int $storeId
     * @param int        $limit
     *
     * @return array|bool
     */
    public function processSubscribersForStore($storeId, $limit)
    {
        // Define default results
        $result = array('total' => 0, 'success' => 0, 'error' => 0);

        // If limit is false or 0, return
        if (!$limit) {
            $this->_helper->writeDebug('  Limit empty. Skipping...');

            return $result;
        }

        // Set Store and StoreId
        if ($storeId instanceof Mage_Core_Model_Store) {
            $store   = $storeId;
            $storeId = $store->getId();
        } else {
            $store   = Mage::app()->getStore($storeId);
            $storeId = $store->getId();
        }

        $this->_helper->writeDebug("Starting Subscriber Opt-In process for store: {$store->getName()} ({$storeId})");

        if (!$store->getConfig(Bronto_Newsletter_Helper_Data::XML_PATH_ENABLED)) {
            $this->_helper->writeDebug('  Module disabled for this store. Skipping...');

            return false;
        }

        $helper = Mage::helper('bronto_newsletter/contact');

        $lists = $helper->getListIds('store', $storeId);

        // Get Subscriber Queue for store
        /* var $subscribers Bronto_Newsletter_Model_Mysql4_Queue_Collection */
        $subscribers = Mage::getModel('bronto_newsletter/queue')
            ->getCollection()
            ->addBrontoNotImportedFilter()
            ->addBrontoNotSuppressedFilter()
            ->orderByUpdatedAt()
            ->addStoreFilter($storeId)
            ->setPageSize($limit)
            ->getItems();

        $actualLists = array();
        foreach ($lists as $listId) {
            if ($list = $helper->getListData($listId, 'store', $storeId)) {
                $actualLists[$listId] = $list->label;
            } else {
                $this->_helper->writeError("The list ({$listId}) was not found. This may indicate that is does not exist. Try re-saving the config.");
            }
        }

        foreach ($subscribers as $subscriber) {
            try {
                /* @var $contact Bronto_Api_Contact_Row */
                $contact = $helper->getContactByEmail($subscriber->getSubscriberEmail(), null, $storeId);

                // If Contact returns false, handle it.
                if (!$contact) {
                    $noContactMessage = 'Could not load contact because email address was empty: ' . var_export($subscriber->getData(), true);
                    $subscriber->setBrontoSuppressed($noContactMessage)->save();
                    Mage::throwException($noContactMessage);
                }

                // If Bronto Status is 'Bounced', mark suppressed, show error and continue foreach
                if ($contact->status == Bronto_Api_Contact::STATUS_BOUNCE) {
                    $bounceMessage = "Subscriber {$contact->email} Has Been Bounced in Bronto";
                    $subscriber->setBrontoSuppressed($bounceMessage)->save();
                    Mage::throwException($bounceMessage);
                }

                // Get List Details
                if ($subscriber->getStatus() == Bronto_Api_Contact::STATUS_ACTIVE || ($helper->isRemoveUnsubs('store', $storeId) && $subscriber->getStatus() == Bronto_Api_Contact::STATUS_UNSUBSCRIBED)) {
                    foreach ($actualLists as $listId => $listName) {
                        if ($subscriber->getStatus() == Bronto_Api_Contact::STATUS_ACTIVE) {
                            $helper->writeInfo("  Adding Contact to list: {$listName}");
                            $contact->addToList($listId);
                        } else {
                            $helper->writeInfo("  Removing Contact from list: {$listName}");
                            $contact->removeFromList($listId);
                        }
                    }
                }

                if ($helper->getUpdateStatus('store', $storeId)) {
                    switch ($subscriber->getStatus()) {
                        case Bronto_Api_Contact::STATUS_UNCONFIRMED:
                        case Bronto_Api_Contact::STATUS_TRANSACTIONAL:
                            if ($contact->id && $contact->status != Bronto_Api_Contact::STATUS_UNSUBSCRIBED) {
                                $helper->writeInfo(
                                    "  Keeping Contact ({$contact->email}) status as: {$contact->status}"
                                );
                                break;
                            }
                            $contact->status = $subscriber->getStatus();
                            $helper->writeInfo("  Setting Contact ({$contact->email}) status to: {$contact->status}");
                            break;

                        case Bronto_Api_Contact::STATUS_ACTIVE:
                            if ($contact->status == Bronto_Api_Contact::STATUS_UNSUBSCRIBED &&
                                $subscriber->getImported() == 2
                            ) {
                                $helper->writeInfo(
                                    "  Keeping Contact ({$contact->email}) status as: {$contact->status}"
                                );
                                break;
                            }
                            $contact->status = $subscriber->getStatus();
                            $helper->writeInfo("  Setting Contact ({$contact->email}) status to: {$contact->status}");
                            break;

                        default:
                            $contact->status = $subscriber->getStatus();
                            $helper->writeInfo("  Setting Contact ({$contact->email}) status to: {$contact->status}");
                            break;
                    }
                }

                $contact->save();

                $subscriber->setImported(1)->save();

                $result['success']++;
            } catch (Exception $e) {
                // 303 = invalid email address
                // 315 = on suppression list
                // 317 = email over 100 characters in length
                if (in_array($e->getCode(), array(303, 315, 317))) {
                    $subscriber->setBrontoSuppressed($e->getMessage());
                }

                $this->_helper->writeError($e);

                $subscriber->setImported(0)->save();
                $result['error']++;
            }

            $result['total']++;
        }

        return $result;
    }

    /**
     * Process queued subscribers
     *
     * @param bool $brontoCron
     *
     * @return array
     */
    public function processSubscribers($brontoCron = false)
    {
        $result = array(
            'total'   => 0,
            'success' => 0,
            'error'   => 0,
        );

        // Only allow cron to run if isset to use mage cron or is coming from bronto cron
        if (Mage::helper('bronto_newsletter')->canUseMageCron() || $brontoCron) {
            $limit = $this->_helper->getLimit();

            $stores = Mage::app()->getStores(true);
            foreach ($stores as $_store) {
                if ($limit <= 0) {
                    continue;
                }
                $storeResult = $this->processSubscribersForStore($_store, $limit);
                $result['total'] += $storeResult['total'];
                $result['success'] += $storeResult['success'];
                $result['error'] += $storeResult['error'];
                $limit = $limit - $storeResult['total'];
            }
        }

        return $result;
    }
}
