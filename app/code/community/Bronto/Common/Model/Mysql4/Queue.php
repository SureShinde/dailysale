<?php

class Bronto_Common_Model_Mysql4_Queue extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * @see parent
     */
    public function _construct()
    {
        $this->_init('bronto_common/queue', 'queue_id');
    }

    /**
     * Flags the obtained the items for holding
     *
     * @param array $items
     * @return Bronto_Common_Model_Mysql4_Queue
     */
    public function flagForHolding($ids)
    {
        $resource = $this->_getWriteAdapter();
        $condition = $this->getIdFieldName() . ' IN (' . implode(',', $ids) . ')';
        $update = array('holding' => 1);
        $resource = $resource->update($this->getMainTable(), $update, $condition);
        return $this;
    }
}
