<?php

class Unirgy_Rma_Model_Observer
{
    public function adminhtml_order_add_create_rma_button()
    {
        $layout = Mage::app()->getLayout();
        if (($soeBlock = $layout->getBlock('sales_order_edit'))
            && Mage::helper('udropship')->isUdropshipOrder(Mage::registry('sales_order'))
            && Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/urma')
            && Mage::registry('sales_order')
            && Mage::helper('urma')->canRMA(Mage::registry('sales_order'))
        ) {
            $soeBlock->addButton('create_urma', array(
                'label'     => Mage::helper('udropship')->__('Create uReturn'),
                'onclick'   => 'setLocation(\'' . $soeBlock->getUrl('adminhtml/urmaadmin_order_rma/new') . '\')',
            ));
        }
    }
}