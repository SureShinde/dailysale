<?php

class Unirgy_Rma_Model_ConvertOrder extends Mage_Sales_Model_Convert_Order
{
    public function toRma(Mage_Sales_Model_Order $order)
    {
        $rma = Mage::getModel('urma/rma');
        $rma->setOrder($order)
            ->setStoreId($order->getStoreId())
            ->setCustomerId($order->getCustomerId())
            ->setBillingAddressId($order->getBillingAddressId())
            ->setShippingAddressId($order->getShippingAddressId());

        Mage::helper('core')->copyFieldset('sales_convert_order', 'to_urma', $order, $rma);
        return $rma;
    }
    public function itemToRmaItem(Mage_Sales_Model_Order_Item $item)
    {
        $rmaItem = Mage::getModel('urma/rma_item');
        $rmaItem->setOrderItem($item)
            ->setProductId($item->getProductId());

        Mage::helper('core')->copyFieldset('sales_convert_order_item', 'to_urma_item', $item, $rmaItem);
        return $rmaItem;
    }
}