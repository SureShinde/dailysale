<?php

class Unirgy_Rma_Model_Mysql4_Rma_Collection extends Mage_Sales_Model_Mysql4_Order_Collection_Abstract
{
    protected $_eventPrefix = 'urma_rma_collection';
    protected $_eventObject = 'rma_collection';
    protected $_orderField = 'main_table.order_id';

    protected function _construct()
    {
        $this->_init('urma/rma');
    }

    protected function _afterLoad()
    {
        $this->walk('afterLoad');
    }
    
    public function addOrders()
    {
        $this->addAttributeToSelect('order_id', 'inner');

        $orderIds = array();
        foreach ($this as $rma) {
            if ($rma->getOrderId()) {
                $orderIds[$rma->getOrderId()] = 1;
            }
        }

        if ($orderIds) {
            $orders = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in'=>array_keys($orderIds)));
            foreach ($this as $rma) {
                $rma->setOrder($orders->getItemById($rma->getOrderId()));
            }
        }
        return $this;
    }
}
