<?php

class Unirgy_Rma_Block_Adminhtml_Rma_View extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        $this->_blockGroup  = 'urma';
        $this->_objectId    = 'rma_id';
        $this->_controller  = 'adminhtml_rma';
        $this->_mode        = 'view';

        parent::__construct();

        $this->_removeButton('reset');
        $this->_removeButton('delete');
        $this->_removeButton('save');

    }
    
	public function getRma()
    {
        return Mage::registry('current_rma');
    }

    public function getHeaderText()
    {
        return Mage::helper('urma')->__('uReturn #%1$s | %2$s', $this->getRma()->getIncrementId(), $this->formatDate($this->getRma()->getCreatedAtDate(), 'medium', true));
    }

    public function getBackUrl()
    {
        return $this->getUrl(
            'adminhtml/sales_order/view',
            array(
                'order_id'  => $this->getRma()->getOrderId(),
                'active_tab'=> 'order_rmas'
            ));
    }

    public function getPrintUrl()
    {
        return $this->getUrl('*/rma/print', array(
            'rma_id' => $this->getRma()->getId()
        ));
    }

    public function updateBackButtonUrl($flag)
    {
        if ($flag) {
            return $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/rma/') . '\')');
        }
        return $this;
    }
}