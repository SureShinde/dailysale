<?php

class Unirgy_DropshipMicrositePro_Block_Vendor_Register_Form_Urlkey extends Varien_Data_Form_Element_Abstract
{
	public function getHtml()
    {
        $this->_renderer = Mage::getSingleton('core/layout')->createBlock('udmspro/vendor_register_renderer_urlkey');
        return parent::getHtml();
    }
}