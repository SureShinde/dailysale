<?php

class Unirgy_Rma_Model_Mysql4_Rma_Item_Collection extends Mage_Sales_Model_Mysql4_Collection_Abstract
{
    protected $_eventPrefix = 'urma_rma_item_collection';
    protected $_eventObject = 'rma_item_collection';

    protected function _construct()
    {
        $this->_init('urma/rma_item');
    }

    public function setRmaFilter($rmaId)
    {
        $this->addFieldToFilter('parent_id', $rmaId);
        return $this;
    }
}
