<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Newsletter_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Endpoint for integrated popup submissions
     */
    public function SubmitAction()
    {
        $email = $this->getRequest()->getParam('emailAddress');
        try {
            if (Mage::helper('bronto_common')->isSubscribeToMagento()) {
                // Note: this will send email confirmation or success, depending
                $subscriber = Mage::getModel('newsletter/subscriber')
                    ->loadByEmail($email)
                    ->setSubscribeSource('popup')
                    ->subscribe($email);
            }
            $this->getResponse()->setBody('success');
        } catch (Exception $e) {
            Mage::helper('bronto_newsletter')->writeError($e);
            $this->getResponse()->setBody($e->getMessage());
        }
    }

    /**
     * Retrieve Checkbox HTML
     */
    public function CheckboxAction()
    {
        $mode = $this->getRequest()->getParam('checkoutMode');

        $this->loadLayout();
        $block = $this->getLayout()
            ->createBlock('bronto_newsletter/checkout_onepage_newsletter')
            ->setTemplate('bronto/newsletter/checkbox.phtml');

        $block->setMode($mode);

        $this->getResponse()->setBody($block->toHtml());
    }

    /**
     * Capture Subscription Action and set session value to store status
     */
    public function SubscribeAction()
    {
        // Get Passed Params
        $starting = (int)$this->getRequest()->getPost('starting', 1);
        $checked  = (int)$this->getRequest()->getPost('checked', 0);
        $email    = (string)$this->getRequest()->getPost('email', false);

        // Pre-define subscribed as null
        $subscribed = null;

        // Get Customer Object from Session
        $customer   = Mage::getSingleton('customer/session')->getCustomer();
        $customerId = $customer->getId();

        // If Customer Get isCustomerSubscribed, otherwise get Subscriber from email and get status
        if ($customerId && !is_null($customerId)) {
            $subscribed = Mage::helper('bronto_newsletter')->isCustomerSubscribed($customer);
        } else if ((!$customerId || is_null($customerId)) && $email) {
            $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
            if ($subscriber->getId() && !is_null($subscriber->getId())) {
                $subscribed = ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) ? 1 : 0;
            }
        }

        // If customer is logged in, and subscribed, and checkbox is unchecked, and starting is not unchecked
        if ($subscribed && 0 == $checked && 0 != $starting) {
            $status = Bronto_Api_Contact::STATUS_UNSUBSCRIBED;
        } elseif (1 == $checked || 1 == $subscribed) {
            $status = Bronto_Api_Contact::STATUS_ACTIVE;
        } else {
            $status = Bronto_Api_Contact::STATUS_TRANSACTIONAL;
        }

        // Get Previous status from session
        $oldStatus = Mage::getSingleton('checkout/session')->getIsSubscribed();

        // Set Session to store subscription status
        Mage::getSingleton('checkout/session')->setIsSubscribed($status);

        // Return subscription status
        $this->getResponse()->setBody($oldStatus . '=>' . $status);
    }
}
