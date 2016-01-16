<?php
/**
 * DropshipPo
 *
 * @author      Fiuze Team
 * @category    Fiuze
 * @package     DropshipPo
 * @copyright   Copyright (c) 2016 Fiuze
 */
class Fiuze_DropshipPo_Model_Mysql4_Po_Collection extends Unirgy_DropshipPo_Model_Mysql4_Po_Collection
{
    /**
     * @return Fiuze_DropshipPo_Model_Mysql4_Po_Collection
     */
    public function addOrders()
    {
        /** @var $helper Unirgy_Dropship_Helper_Data */
        $helper = Mage::helper('udropship');
        if (!$helper->isSalesFlat()) {
            $this->addAttributeToSelect('*', 'inner');
        }

        $orderIds = array();
        foreach ($this as $po) {
            /** @var $po Unirgy_DropshipPo_Model_Po */
            if ($po->getData('order_id')) {
                $orderIds[$po->getData('order_id')] = 1;
            }
        }

        if ($orderIds) {
            /** @var $orders Mage_Sales_Model_Resource_Order_Collection */
            $orders = Mage::getModel('sales/order')->getCollection();
            $orders->addAttributeToSelect('*');
            $orders->addAttributeToFilter('entity_id', array('in' => array_keys($orderIds)));
            foreach ($this as $po) {
                /** @var $order Mage_Sales_Model_Order */
                $order = $orders->getItemById($po->getData('order_id'));
                $po->setData('order', $order);
            }
        }

        return $this;
    }
}
