<?php


class MageWorx_InstantCart_Block_Displayoption extends Mage_Core_Block_Template
{
    const XML_DISPLAYOPTION = 'mageworx_customers/icart/enabled_display';
    protected function _toHtml() {
        $html = parent::_toHtml();
        return $html;
    }

    public function getDisplayOption(){
        return Mage::getStoreConfig(self::XML_DISPLAYOPTION);
    }

}