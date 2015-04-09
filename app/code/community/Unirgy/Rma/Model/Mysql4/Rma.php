<?php

class Unirgy_Rma_Model_Mysql4_Rma extends Mage_Sales_Model_Mysql4_Order_Abstract
{
    protected $_eventPrefix = 'urma_rma_resource';
    protected $_grid = true;
    protected $_useIncrementId = true;
    protected $_entityTypeForIncrementId = 'urma_rma';

    protected function _construct()
    {
        $this->_init('urma/rma', 'entity_id');
    }

    protected function _initVirtualGridColumns()
    {
        parent::_initVirtualGridColumns();
        $this->addVirtualGridColumn(
                'shipping_name',
                'sales/order_address',
                array('shipping_address_id' => 'entity_id'),
                'CONCAT(IFNULL({{table}}.firstname, ""), " ", IFNULL({{table}}.lastname, ""))'
            )
            ->addVirtualGridColumn(
                'order_increment_id',
                'sales/order',
                array('order_id' => 'entity_id'),
                'increment_id'
            )
            ->addVirtualGridColumn(
                'order_created_at',
                'sales/order',
                array('order_id' => 'entity_id'),
                'created_at'
            )
            ;

        return $this;
    }
}
