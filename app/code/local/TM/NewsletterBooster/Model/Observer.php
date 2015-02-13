<?php

class TM_NewsletterBooster_Model_Observer
{
    public function scheduledSendCampaign($schedule)
    {
        $countOfSubscritions = 50;
        if (Mage::getStoreConfig('newsletterbooster/general/mail_count')) {
            $countOfSubscritions = Mage::getStoreConfig('newsletterbooster/general/mail_count');
        }

        $collection = Mage::getModel('newsletterbooster/queue')->getCollection()
            ->addOnlyForSendingFilter();

        $collection->walk('sendPerSubscriber', array($countOfSubscritions));

        $collection->walk('sendPerGuest', array($countOfSubscritions));

        return $this;
    }

    /**
     * <!--Events for newsletter-->
     * @param Varien_Event_Observer $observer
     */
    public function subscribedToNewsletter(Varien_Event_Observer $observer)
    {
        $object = $observer->getSubscriber();

        switch($object->getData('subscriber_status')){
            case Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED:
                $email = $object->getData('subscriber_email');
                $unsubscribe = Mage::getModel('newsletterbooster/unsubscribe')->load($email, 'email');
                $unsubscribe->delete();
                break;
            case Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED:
                $email = $object->getData('subscriber_email');
                $subscriber = Mage::getModel('newsletterbooster/subscriber')->load($email, 'email');
                $subscriber->delete();
                break;
            default:
                break;
        }
    }
}