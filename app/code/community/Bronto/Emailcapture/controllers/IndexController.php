<?php

/**
 * @package     Bronto\Emailcapture
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Emailcapture_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Capture email address provided from input field
     */
    public function captureAction()
    {
        $emailAddress = $this->getRequest()->getParam('emailAddress', null);

        // If Email Address isn't valid, don't worry
        if (Zend_Validate::is($emailAddress, 'EmailAddress')) {
            try {
                Mage::getModel('bronto_emailcapture/queue')->updateEmail($emailAddress);
                $this->updateQuote();
            } catch (Exception $e) {
                Mage::helper('bronto_emailcapture')->writeDebug($e->getMessage());
            }
        }

        return;
    }

    /**
     * Update any attached Quote with email address
     */
    public function updateQuote()
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        if ($quote->getId()) {
            Mage::getModel('bronto_emailcapture/observer')->updateQuote($quote);
        }
    }
}