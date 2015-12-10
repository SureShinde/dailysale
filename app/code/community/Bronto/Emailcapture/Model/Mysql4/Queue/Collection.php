<?php

/**
 * @package   Bronto\Emailcapture
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_EmailCapture_Model_Mysql4_Queue_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialize Model
     *
     * @return void
     * @access public
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_emailcapture/queue');
    }
}