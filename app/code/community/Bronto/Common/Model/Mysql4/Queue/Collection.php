<?php

class Bronto_Common_Model_Mysql4_Queue_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * @see parent
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_common/queue');
    }

    /**
     * Only gets the entries that aren't flagged for holding
     *
     * @return Bronto_Common_Model_Mysql4_Queue_Collection
     */
    public function getReadyEntries()
    {
        return $this->addFieldToFilter('holding', array('eq' => 0));
    }

    /**
     * Only gets the entries for the store in question
     *
     * @return Bronto_Common_Model_Mysql4_Queue_Collection
     */
    public function getEntriesForStore($storeId)
    {
        return $this->addFieldToFilter('store_id', array('eq' => $storeId));
    }

    /**
     * Gets the oldest to pop out the queue
     *
     * @return Bronto_Common_Model_Mysql4_Queue_Collection
     */
    public function orderByOldest()
    {
        return $this->addOrder('created_at', self::SORT_ORDER_ASC);
    }
}
