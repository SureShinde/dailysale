<?php

class Unirgy_Rma_Model_Mysql4_Rma_Comment_Collection extends Mage_Sales_Model_Mysql4_Order_Comment_Collection_Abstract
{
    protected $_eventPrefix = 'urma_rma_comment_collection';
    protected $_eventObject = 'rma_comment_collection';

    protected function _construct()
    {
        $this->_init('urma/rma_comment');
    }

    public function setRmaFilter($rmaId)
    {
        return $this->setParentFilter($rmaId);
    }

    public function setCreatedAtOrder($direction='desc')
    {
        $this->setOrder('created_at', $direction);
        return $this;
    }
}
