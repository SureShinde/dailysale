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
}