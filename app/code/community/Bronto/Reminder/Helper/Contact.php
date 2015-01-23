<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Helper_Contact extends Bronto_Common_Helper_Contact
{
    /**
     * Retrieve helper module name
     *
     * @return string
     */
    protected function _getModuleName()
    {
        return 'Bronto_Reminder';
    }

    /**
     * Get Bronto Contact Object by Email Address
     *
     * @param string $email
     * @param string $customSource
     * @param null   $store
     *
     * @return Bronto_Api_Contact_Row
     */
    public function getContactByEmail($email, $customSource = 'bronto_reminder', $store = null)
    {
        return parent::getContactByEmail($email, $customSource, $store);
    }
}
