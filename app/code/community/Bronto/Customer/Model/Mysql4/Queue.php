<?php

/**
 * @package   Bronto\Customer
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Customer_Model_Mysql4_Queue extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Primary key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Initialize Model
     *
     * @return void
     * @access public
     */
    public function _construct()
    {
        $this->_init('bronto_customer/queue', 'queue_id');
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