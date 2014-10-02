<?php

class TM_SegmentationSuite_Model_Mysql4_Store extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('segmentationsuite/store', 'id');
    }
}