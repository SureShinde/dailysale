<?php

class Unirgy_DropshipMicrositePro_Block_Vendor_Register_Form_Termcond extends Varien_Data_Form_Element_Checkboxes
{
    public function getLabelHtml($idSuffix = '')
    {
        return '';
    }
    protected function _optionToHtml($option)
    {
        $id = $this->getHtmlId().'_'.$this->_escape($option['value']);

        $html = '<li><input id="'.$id.'"';
        foreach ($this->getHtmlAttributes() as $attribute) {
            if ($value = $this->getDataUsingMethod($attribute)) {
                $html .= ' '.$attribute.'="'.$value.'"';
            }
        }
        $html .= ' value="'.$option['value'].'" />'
            . ' <label for="'.$id.'">' . $this->getLabel() . ( $this->getRequired() ? ' <span class="required">*</span>' : '' ) . '</label></li>'
            . "\n";
        return $html;
    }
}