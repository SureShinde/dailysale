<?php

class Unirgy_Rma_Block_Adminhtml_Rma_Create extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'urma';
        $this->_objectId = 'order_id';
        $this->_controller = 'adminhtml_rma';
        $this->_mode = 'create';

        parent::__construct();

        //$this->_updateButton('save', 'label', Mage::helper('sales')->__('Submit Shipment'));
        $this->_removeButton('save');
        $this->_removeButton('delete');
    }

    /**
     * Retrieve shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getRma()
    {
        return Mage::registry('current_rma');
    }

    public function getHeaderText()
    {
        $header = Mage::helper('sales')->__('New Return for Order #%s', $this->getRma()->getOrder()->getRealOrderId());
        return $header;
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/sales_order/view', array('order_id'=>$this->getRma()->getOrderId()));
    }
}
