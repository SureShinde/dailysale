<?php

class Unirgy_Rma_Block_Order_Info extends Mage_Sales_Block_Order_Info
{
    public function getLinks()
    {
        $order = $this->getOrder();
        Mage::helper('urma')->initOrderRmasCollection($order);
        if (!$order->getHasUrmas()) {
            unset($this->_links['rma']);
        }
        return parent::getLinks();
    }
}