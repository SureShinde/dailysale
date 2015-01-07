<?php

/**
 * @package   Bronto\Customer
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Customer_Model_Queue extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_customer/queue');
    }

    /**
     * Retrieve Customer Queue Row
     *
     * @param int $customerId
     * @param int $storeId
     *
     * @return Bronto_Customer_Model_Queue
     */
    public function getCustomerRow($customerId, $storeId)
    {
        // Create Collection
        $collection = $this->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('store_id', $storeId);

        // Handle Results
        if ($collection->count() == 1) {
            return $collection->getFirstItem();
        } else {
            $this->setCustomerId($customerId)
                ->setStoreId($storeId);
        }

        return $this;
    }

    /**
     * Get Count of missing customers
     *
     * @return int
     */
    public function getMissingCustomersCount()
    {
        // Get Resources
        $resource = $this->getResource();
        $adapter  = $resource->getWriteAdapter();

        // Build Select Statement
        $select = $adapter->select();
        $select->from(
            array('customer' => $resource->getTable('customer/entity')),
            array(new Zend_Db_Expr('COUNT(entity_id) as count'))
        )
            ->where('NOT EXISTS (?)', $this->_getSubselect($resource, $adapter));

        // Get Results
        $result = $adapter->query($select)->fetch();

        if (array_key_exists('count', $result)) {
            return (int)$result['count'];
        } else {
            return 0;
        }
    }

    /**
     * @param Bronto_Customer_Model_Mysql4_Queue $resource
     * @param                                    $adapter
     *
     * @return Varien_Db_Select
     */
    private function _getSubselect($resource, $adapter)
    {
        // Build Sub-Select Statement
        $subselect = $adapter->select()
            ->from(
                array('queue' => $resource->getTable('bronto_customer/queue')),
                array(new Zend_Db_Expr(1))
            )
            ->where('queue.customer_id = customer.entity_id');

        return $subselect;
    }

    /**
     * Get collection of customers which aren't already in the queue, but should be
     *
     * @return array
     */
    public function getMissingCustomers()
    {
        // Get Resources
        $resource = $this->getResource();
        $adapter  = $resource->getWriteAdapter();

        // Get Sync Limit Value
        $count = Mage::helper('bronto_customer')->getSyncLimit();

        // Build Select Statement
        $select = $adapter->select();
        $select->from(
            array('customer' => $resource->getTable('customer/entity')),
            array('entity_id', 'created_at', 'store_id')
        )
            ->where('NOT EXISTS (?)', $this->_getSubselect($resource, $adapter))
            ->limit($count);

        // Get Results
        $result = $adapter->query($select)->fetchAll();

        return $result;
    }
}