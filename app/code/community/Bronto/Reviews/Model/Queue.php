<?php

/**
 * @package     Bronto\Reviews
 * @copyright   2011-2013 Bronto Software, Inc.
 * @version     0.0.1
 */
class Bronto_Reviews_Model_Queue extends Mage_Core_Model_Abstract
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
     * Loads the delivery entry by order and delivery ID
     *
     * @param int $orderId
     * @param mixed $deliveryId
     * @return Bronto_Reviews_Model_Queue
     */
    public function loadByOrderAndDeliveryId($orderId, $deliveryId = null)
    {
        $this->setOrderId($orderId);
        $this->setDeliveryId($deliveryId);
        $this->_getResource()->loadByOrderAndDeliveryId($this, $orderId, $deliveryId);
        return $this;
    }

    /**
     * Removes old deliveries from the table
     *
     * @param datetime $date
     */
    public function flushDeliveries($date = null)
    {
        if (is_null($date)) {
            $date = date('Y-m-d H:i:s');
        }
        $this->_getResource()->flushDeliveries($date);
    }
}
