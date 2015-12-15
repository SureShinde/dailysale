<?php

class Bronto_Reviews_Model_Mysql4_Log extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * @see parent
     */
    protected function _construct()
    {
        $this->_init('bronto_reviews/log', 'log_id');
    }

    /**
     * Getst he log with an order and delivery
     *
     * @param Bronto_Reviews_Model_Log $object
     * @param int $orderId
     * @param mixed $deliveryId
     * @return void
     */
    public function loadByOrderAndDeliveryId($object, $orderId, $deliveryId)
    {
        $this->_loadSelectWithNull($object, $orderId, 'delivery_id', $deliveryId);
    }

    /**
     * Gets the log with an order id and postId
     *
     * @param Bronto_Reviews_Model_Log $object
     * @param int $orderId
     * @param mixed $postId
     * @return void
     */
    public function loadByOrderAndPost($object, $orderId, $postId)
    {
        $this->_loadSelectWithNull($object, $orderId, 'post_id', $postId);
    }

    /**
     * Clears logs that are old or cancelled
     *
     * @param mixed $time
     */
    public function clearOld($time = null)
    {
        $date = date('Y-m-d H:i:s', empty($time) ? time() : $time);
        $write = $this->_getWriteAdapter();
        $write->delete($this->getMainTable(), $write->quoteInto('delivery_date <= ?', $date));
    }

    /**
     * Clears logs that have been cancelled
     */
    public function clearCancelled()
    {
        $write = $this->_getWriteAdapter();
        $write->delete($this->getMainTable(), $write->quoteInto('cancelled = ?', 1));
    }

    /**
     * Creates a query that builds a select for a single log and order
     *
     * @param Bronto_Reviews_Model_Log $object
     * @param int $orderId
     * @param string $fieldName
     * @param mixed $fieldValue
     * @return void
     */
    protected function _loadSelectWithNull($object, $orderId, $fieldName, $fieldValue)
    {
        $read = $this->_getReadAdapter();
        $select = $this->_getLoadSelect('order_id', $orderId, $object);
        $field = $read->quoteIdentifier(sprintf("%s.%s", $this->getMainTable(), $fieldName));
        $cancelled = $read->quoteIdentifier(sprintf("%s.%s", $this->getMainTable(), 'cancelled'));
        if (is_null($fieldValue)) {
            $select->where("{$field} IS NULL");
        } else {
            $select->where("{$field} = ?", $fieldValue);
        }
        $select->where("{$cancelled} = ?", 0);
        $data = $read->fetchRow($select);
        if ($data) {
            $object->setData($data);
        }
    }
}
