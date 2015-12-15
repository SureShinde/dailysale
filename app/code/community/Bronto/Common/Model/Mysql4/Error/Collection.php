<?php

class Bronto_Common_Model_Mysql4_Error_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * @see parent
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('bronto_common/error');
    }

    /**
     * Orders the items by last attempt time
     *
     * @return Bronto_Common_Model_Mysql4_Error_Collection
     */
    public function orderByOldest()
    {
        return $this->addOrder('last_attempt', self::SORT_ORDER_ASC);
    }

    /**
     * Gets the entries that have less than the threshold attempts
     *
     * @param int $threshold
     * @return Bronto_Common_Model_Mysql4_Error_Collection
     */
    public function addAttemptThreshold($threshold)
    {
        $this->addFieldToFilter('attempts', array('lt' => $threshold));
        return $this;
    }
}
