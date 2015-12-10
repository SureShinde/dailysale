<?php

class Unirgy_Rma_Block_Adminhtml_SalesOrderView_Info extends Mage_Adminhtml_Block_Sales_Order_View_Info
{
    public function getCustomerViewUrl()
    {
        if ($this->getOrder()->getCustomerIsGuest()) {
            return false;
        }
        return $this->getUrl('adminhtml/customer/edit', array('id' => $this->getOrder()->getCustomerId()));
    }

    public function getViewUrl($orderId)
    {
        return $this->getUrl('adminhtml/sales_order/view', array('order_id'=>$orderId));
    }
    public function getRmaReasonName()
    {
        return Mage::registry('current_rma') ? Mage::registry('current_rma')->getRmaReasonName() : '';
    }
}