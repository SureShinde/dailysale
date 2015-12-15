<?php

/**
 * @package     Bronto\Reviews
 * @copyright   2011-2013 Bronto Software, Inc.
 * @version     0.0.1
 */
class Bronto_Reviews_Model_Mysql4_Queue_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_reviews/queue');
    }

    /**
     * Returns all of the delivery entries for an order
     *
     * @param int $orderId
     * @return Bronto_Reviews_Model_Mysql4_Queue_Collection
     */
    public function filterByOrderId($orderId)
    {
        return $this->addFieldToFilter('order_id', array('eq' => $orderId));
    }
}
