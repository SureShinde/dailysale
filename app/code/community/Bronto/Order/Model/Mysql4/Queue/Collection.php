<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Model_Mysql4_Queue_Collection
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
        $this->_init('bronto_order/queue');
    }

    /**
     * @return Bronto_Order_Model_Mysql4_Queue_Collection
     */
    public function addBrontoImportedFilter()
    {
        $this->addFieldToFilter('bronto_imported', array('notnull' => true));

        return $this;
    }

    /**
     * @return Bronto_Order_Model_Mysql4_Queue_Collection
     */
    public function addBrontoSuppressedFilter()
    {
        $this->addFieldToFilter('bronto_suppressed', array('notnull' => true));

        return $this;
    }

    /**
     * @return Bronto_Order_Model_Mysql4_Queue_Collection
     */
    public function addBrontoNotImportedFilter()
    {
        $this->addFieldToFilter('bronto_imported', array('null' => true));

        return $this;
    }

    /**
     * @return Bronto_Order_Model_Mysql4_Queue_Collection
     */
    public function addBrontoNotSuppressedFilter()
    {
        $this->addFieldToFilter('bronto_suppressed', array('null' => true));

        return $this;
    }

    public function addBrontoHasOrderFilter()
    {
        $this->addFieldToFilter('order_id', array('neq' => 0));

        return $this;
    }

    /**
     * @param mixed $storeIds (null, int|string, array, array may contain null)
     *
     * @return Bronto_Order_Model_Mysql4_Queue_Collection
     */
    public function addStoreFilter($storeIds)
    {
        $nullCheck = false;

        if (!is_array($storeIds)) {
            $storeIds = array($storeIds);
        }

        $storeIds = array_unique($storeIds);

        if ($index = array_search(null, $storeIds, true)) {
            unset($storeIds[$index]);
            $nullCheck = true;
        }

        if ($nullCheck) {
            $this->getSelect()->where('store_id IN(?) OR store_id IS NULL', $storeIds);
        } else {
            $this->getSelect()->where('store_id IN(?)', $storeIds);
        }

        return $this;
    }

    /**
     * Sort order by order created_at date
     *
     * @param string $dir
     *
     * @return Bronto_Order_Model_Mysql4_Queue_Collection
     */
    public function orderByCreatedAt($dir = self::SORT_ORDER_DESC)
    {
        $this->getSelect()->order("created_at $dir");

        return $this;
    }

    /**
     * Sort order by order updated_at date
     *
     * @param string $dir
     *
     * @return Bronto_Order_Model_Mysql4_Queue_Collection
     */
    public function orderByUpdatedAt($dir = self::SORT_ORDER_DESC)
    {
        $this->getSelect()->order("updated_at $dir");

        return $this;
    }
}