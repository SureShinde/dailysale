<?php

class Unirgy_Rma_Model_Mysql4_Rma_Grid_Collection extends Unirgy_Rma_Model_Mysql4_Rma_Collection
{
    protected $_eventPrefix = 'urma_rma_grid_collection';
    protected $_eventObject = 'rma_grid_collection';

    protected function _construct()
    {
        parent::_construct();
        $this->setMainTable('urma/rma_grid');
    }


}
