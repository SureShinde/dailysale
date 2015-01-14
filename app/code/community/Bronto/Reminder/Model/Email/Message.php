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
     * @param Bronto_Api_Contact_Row $contact
     * @param Bronto_Api_Message_Row $message
     *
     * @return void
     */
    protected function _beforeSend(Bronto_Api_Contact_Row $contact, Bronto_Api_Message_Row $message)
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
     * @param Bronto_Api_Delivery_Row $delivery (optional)
     *
     * @return void
     */
    protected function _afterSend($success, $error = null, Bronto_Api_Delivery_Row $delivery = null)
    {
        Mage::dispatchEvent('bronto_reminder_send_after');

        if (!is_null($delivery)) {
            $helper = Mage::helper($this->_helper);
            $status = $success ? "Successful" : "Failed";

            $helper->writeVerboseDebug("===== $status Reminder Delivery =====", $this->_apiLogFile);
            $helper->writeVerboseDebug(var_export($delivery->getApi()->getLastRequest(), true), $this->_apiLogFile);
            $helper->writeVerboseDebug(var_export($delivery->getApi()->getLastResponse(), true), $this->_apiLogFile);
        }

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
