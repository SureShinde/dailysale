<?php

/**
 * @package   Bronto\Newsletter
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Newsletter_Model_Mysql4_Queue extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Primary key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function _construct()
    {
        $this->_init('bronto_newsletter/queue', 'queue_id');
    }

    /**
     * Get Write adapter instance
     *
     * @return Varien_Db_Adapter_Interface
     */
    public function getWriteAdapter()
    {
        return $this->_getWriteAdapter();
    }
}