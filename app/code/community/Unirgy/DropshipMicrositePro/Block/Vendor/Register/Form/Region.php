<?php

class Unirgy_DropshipMicrositePro_Block_Vendor_Register_Form_Region extends Varien_Data_Form_Element_Select
{
	public function getHtml()
    {
        $this->_renderer = Mage::getSingleton('core/layout')->createBlock('udmspro/vendor_register_renderer_region');
        return parent::getHtml();
    }
}