<?php

class Unirgy_DropshipMicrositePro_Block_Vendor_Register_Form_Image extends Varien_Data_Form_Element_Image
{
    public function getElementHtml()
    {
        $html = '';

        if ((string)$this->getValue()) {
            $url = $this->_getUrl();

            if( !preg_match("/^http\:\/\/|https\:\/\//", $url) ) {
                $url = Mage::getBaseUrl('media') . $url;
            }

            $html = '<a href="' . $url . '"'
                . ' onclick="imagePreview(\'' . $this->getHtmlId() . '_image\'); return false;">'
                . '<img src="' . $url . '" id="' . $this->getHtmlId() . '_image" title="' . $this->getValue() . '"'
                . ' alt="' . $this->getValue() . '" height="22" width="22" class="small-image-preview v-middle" />'
                . '</a> ';
        }
        $this->addClass('input-file');
        $this->removeClass('input-text');
        $html .= Varien_Data_Form_Element_Abstract::getElementHtml();
        $html .= $this->_getDeleteCheckbox();

        return $html;
    }
}