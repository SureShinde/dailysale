<?php

/**
 * @package     Bronto\Reviews
 * @copyright   2011-2013 Bronto Software, Inc.
 * @version     0.0.1
 */
class Bronto_Reviews_Model_Mysql4_Queue extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize Model
     *
     * @return void
     * @access public
     */
    public function _construct()
    {
        $this->_init('bronto_reviews/queue', 'entity_id');
    }

    /**
     * Loads the delivery entry by order ID
     *
     * @param Bronto_Reviews_Model_Queue $object
     * @param int $orderId
     * @param mixed $deliveryId
     */
    public function loadByOrderAndDeliveryId($object, $orderId, $deliveryId = null)
    {
        $read = $this->_getReadAdapter();
        $select = $this->_getLoadSelect('order_id', $orderId, $object);
        $deliveryField = $read->quoteIdentifier(sprintf("%s.%s", $this->getMainTable(), 'delivery_id'));
        if (is_null($deliveryId)) {
            $select->where("{$deliveryField} IS NULL");
        } else {
            $select->where("{$deliveryField} = ?", $deliveryId);
        }
        $data = $read->fetchRow($select);
        if ($data) {
            $object->setData($data);
        }
    }

    /**
     * Removes old deliveries from the table
     *
     * @param datetime $date
     */
    public function flushDeliveries($date)
    {
        $this->_getWriteAdapter()->delete(
            $this->getMainTable(),
            '(' . $this->_getWriteAdapter()->quoteInto('delivery_date <= ?', $date) . ' AND delivery_date IS NOT NULL)'
        );
    }
}
