<?php

/**
 * @package     Bronto\Reviews
 * @copyright   2011-2013 Bronto Software, Inc.
 * @version     0.0.1
 */
class Bronto_Reviews_Helper_Contact 
    extends Bronto_Common_Helper_Contact
{
    /**
     * Retrieve helper module name
     *
     * @return string
     */
    protected function _getModuleName()
    {
        return 'Bronto_Reviews';
    }

    /**
     * @param string $email
     * @return Bronto_Api_Contact_Row
     */
    public function getContactByEmail($email, $customSource = 'bronto_reviews', $store = null)
    {
        return parent::getContactByEmail($email, $customSource, $store);
    }
}
