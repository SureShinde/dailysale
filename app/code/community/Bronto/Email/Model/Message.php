<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Model_Message extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_email/message');
    }
}