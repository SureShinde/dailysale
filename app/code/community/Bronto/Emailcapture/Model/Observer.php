<?php

/**
 * @package   Bronto/Emailcapture
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Emailcapture_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Observe Newsletter Save and add Subscriber Details to Queue Entry
     *
     * @param  Varien_Event_Observer $observer
     *
     * @return Varien_Event_Observer
     */
    public function newsletterSubscriberSaveAfter(Varien_Event_Observer $observer)
    {
        if (!$subscriber = $observer->getEvent()->getSubscriber()) {
            return $observer;
        }

        try {
            Mage::getModel('bronto_emailcapture/queue')->updateEmail($subscriber->getSubscriberEmail());
        } catch (Exception $e) {
            Mage::helper('bronto_emailcapture')->writeDebug($e->getMessage());
        }

        return $observer;
    }

    /**
     * Add Current Email to Quote if Email not already set
     *
     * @param Varien_Event_Observer $observer
     */
    public function addEmailToQuote(Varien_Event_Observer $observer)
    {
        $object = $observer->getEvent()->getDataObject();

        if ($object instanceof Mage_Sales_Model_Quote) {
            $this->updateQuote($object);
        }
    }

    /**
     * Update Quote Email Address if is guest and current email address assigned doesn't match new email
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    public function updateQuote(Mage_Sales_Model_Quote $quote)
    {
        $queue = Mage::getModel('bronto_emailcapture/queue');
        $currentEmail = $queue->getCurrentEmail();
        if (is_null($quote->getCustomerId()) && $queue->isValidEmail($currentEmail) && $quote->getCustomerEmail() !== $currentEmail) {
            $quote->setCustomerEmail(Mage::getModel('bronto_emailcapture/queue')->getCurrentEmail())->save();
        }
    }

    /**
     * Function to trigger flushing all old captured emails from queue
     */
    public function flushQueue()
    {
        Mage::getModel('bronto_emailcapture/queue')->flushQueue();
    }
}
