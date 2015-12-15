<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Model_Email_Message extends Bronto_Common_Model_Email_Template
{
    /**
     * @var string
     */
    protected $_helper = 'bronto_reminder';

    /**
     * @var string
     */
    protected $_apiLogFile = 'bronto_reminder_api.log';

    /**
     * @see parent
     */
    protected function _emailClass()
    {
        return 'bronto_reminder/email_message';
    }

    /**
     * @see parent
     */
    protected function _queuable()
    {
        return false;
    }

    /**
     * Log about the functionality of sending the email before it goes out
     *
     * @param Bronto_Api_Model_Contact $contact
     * @param Bronto_Api_Model_Message $message
     *
     * @return void
     */
    protected function _beforeSend(Bronto_Api_Model_Contact $contact, Bronto_Api_Model_Message $message)
    {
        Mage::dispatchEvent('bronto_reminder_send_before');

        if (Mage::helper('bronto_reminder')->isLogEnabled()) {
            $this->_log = Mage::getModel('bronto_reminder/delivery');
            $this->_log->setCustomerEmail($contact->email);
            $this->_log->setContactId($contact->id);
            $this->_log->setMessageId($message->id);
            $this->_log->setMessageName($message->name);
            $this->_log->setSuccess(0);
            $this->_log->setSentAt(new Zend_Db_Expr('NOW()'));
            $this->_log->save();
        }
    }

    /**
     * Log the Delivery API call
     *
     * @param bool                    $success
     * @param string                  $error    (optional)
     * @param Bronto_Api_ $delivery (optional)
     *
     * @return void
     */
    protected function _afterSend($success, $error = null, Bronto_Api_Model_Delivery $delivery = null)
    {
        Mage::dispatchEvent('bronto_reminder_send_after');
        if (Mage::helper('bronto_reminder')->isLogEnabled()) {
            $this->_log->setSuccess((int)$success);
            if (!empty($error)) {
                $this->_log->setError($error);
            }
            if ($delivery) {
                $this->_log->setDeliveryId($delivery->id);
                if (Mage::helper('bronto_reminder')->isLogFieldsEnabled()) {
                    $this->_log->setFields(serialize($delivery->getFields()));
                }
            }
            $this->_log->save();
            $this->_log = null;
        }
    }
}
