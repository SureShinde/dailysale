<?php

class Unirgy_Rma_Block_Adminhtml_Rma_Create_Form extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    public function getOrder()
    {
        return $this->getRma()->getOrder();
    }

    public function getSource()
    {
        return $this->getRma();
    }

    public function getRma()
    {
        return Mage::registry('current_rma');
    }

    public function getPaymentHtml()
    {
        return $this->getChildHtml('order_payment');
    }

    public function getItemsHtml()
    {
        return $this->getChildHtml('order_items');
    }

    public function getSaveUrl()
    {
        return $this->getUrl('urmaadmin/order_rma/save', array('order_id' => $this->getRma()->getOrderId()));
    }
}
