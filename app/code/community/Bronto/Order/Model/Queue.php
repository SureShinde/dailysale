<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Model_Queue extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_order/queue');
    }

    /**
     * Retrieve Order Queue Row
     *
     * @param bool $orderId
     * @param bool $quoteId
     * @param bool $storeId
     *
     * @return $this
     */
    public function getOrderRow($orderId = false, $quoteId = false, $storeId = false)
    {
        // Either OrderID or QuoteID must be present as well as StoreID
        if ((false === $orderId && false === $quoteId) || false === $storeId) {
            return $this;
        }

        // Create Collection
        $collection = $this->getCollection();

        // Add Filters
        if (($quoteId > 0) && ($orderId > 0)) {
            $collection->getSelect()->where("`quote_id` = $quoteId AND `order_id` = $orderId");
        } elseif (($quoteId > 0)) {
            $collection->addFieldToFilter('quote_id', $quoteId);
        } elseif (($orderId > 0)) {
            $collection->addFieldToFilter('order_id', $orderId);
        }
        $collection->addFieldToFilter('store_id', $storeId);

        try {
            // Handle Results
            if ($collection->count() == 1) {
                $order = $collection->getFirstItem();
                if (($quoteId > 0)) {
                    $order->setQuoteId($quoteId);
                }
                if (($orderId > 0)) {
                    $order->setOrderId($orderId);
                }
                $order->save();

                return $order;
            } else if ($collection->count() > 1) {
                // This might be the same quote id
                foreach ($collection->getItems() as $row) {
                    if ($row->getOrderId() == 0) {
                        $row->delete();
                    }
                }
                return $row;
            } else {
                if (($quoteId > 0)) {
                    $this->setQuoteId($quoteId);
                }
                if (($orderId > 0)) {
                    $this->setOrderId($orderId);
                }

                $this->setStoreId($storeId);
            }
        } catch (Exception $e) {
            Mage::helper('bronto_order')->writeDebug("Exception Thrown pulling order row");
        }

        return $this;
    }

    /**
     * Get Count of missing orders
     *
     * @return int
     */
    public function getMissingOrdersCount()
    {
        // Get Resources
        $resource = $this->getResource();
        $adapter  = $resource->getWriteAdapter();

        // Build Select Statement
        $select = $adapter->select();
        $select->from(
            array('order' => $resource->getTable('sales/order')), array(new Zend_Db_Expr('COUNT(entity_id) as count'))
        )
            ->where('`order`.`entity_id` NOT IN (?)', $this->_getSubselect($resource, $adapter));

        // Get Results
        $result = $adapter->query($select)->fetch();

        if (array_key_exists('count', $result)) {
            return $result['count'];
        } else {
            return 0;
        }
    }

    /**
     * Get Sub-Select Statement that limits results
     *
     * @param Bronto_Order_Model_Mysql4_Queue $resource
     * @param                                 $adapter
     *
     * @return Varien_Db_Select
     */
    private function _getSubselect($resource, $adapter)
    {
        // Build Sub-Select Statement
        $subselect = $adapter->select()
            ->from(
                array('queue' => $resource->getTable('bronto_order/queue')), array('order_id')
            );

        return $subselect;
    }

    /**
     * Get collection of orders which aren't already in the queue, but should be
     *
     * @return array
     */
    public function getMissingOrders()
    {
        // Get Resources
        $resource = $this->getResource();
        $adapter  = $resource->getWriteAdapter();

        // Get Sync Limit Value
        $count = Mage::helper('bronto_order')->getSyncLimit();

        // Build Select Statement
        $select = $adapter->select()
            ->from(
                array('order' => $resource->getTable('sales/order')), array('entity_id', 'store_id', 'quote_id', 'created_at')
            )
            ->where('`order`.`entity_id` NOT IN (?)', $this->_getSubselect($resource, $adapter))
            ->limit($count);

        // Get Results
        $result = $adapter->query($select)->fetchAll();

        return $result;
    }

}
