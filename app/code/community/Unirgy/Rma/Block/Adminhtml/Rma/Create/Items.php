<?php

class Unirgy_Rma_Block_Adminhtml_Rma_Create_Items extends Mage_Adminhtml_Block_Sales_Items_Abstract
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

    protected function _beforeToHtml()
    {
        $this->setChild(
            'submit_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                'label'     => Mage::helper('udropship')->__('Submit Return'),
                'class'     => 'save submit-button',
                'onclick'   => 'editForm.submit()',
            ))
        );

        return parent::_beforeToHtml();
    }

}
