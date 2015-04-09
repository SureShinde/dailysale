<?php

class Unirgy_Rma_Model_Mysql4_Rma_Comment extends Mage_Sales_Model_Mysql4_Order_Abstract
{
    protected $_eventPrefix = 'urma_rma_comment_resource';

    protected function _construct()
    {
        $this->_init('urma/rma_comment', 'entity_id');
    }
}
