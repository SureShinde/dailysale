<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Model_Mysql4_Message extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Primery key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init('bronto_email/message', 'core_template_id');
    }
}
