<?php

class Unirgy_Rma_Model_Mysql4_Rma_Track extends Mage_Sales_Model_Mysql4_Order_Abstract
{
    protected $_eventPrefix = 'urma_rma_track_resource';

    protected function _construct()
    {
        $this->_init('urma/rma_track', 'entity_id');
    }
}
