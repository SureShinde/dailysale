<?php

class Unirgy_DropshipMicrositePro_Block_Vendor_Register_Form_Radios extends Varien_Data_Form_Element_Radios
{
    public function getElementHtml()
    {
        $html = '';
        $value = $this->getValue();
        if ($values = $this->getValues()) {
            $values = Mage::helper('core')->decorateArray($values);
            foreach ($values as $option) {
                $html.= $this->_optionToHtml($option, $value);
            }
        }
        $html.= $this->getAfterElementHtml();
        return $html;
    }
    protected function _optionToHtml($option, $selected)
    {
        $isLast = false;
        if (is_array($option)) {
            $isLast = @$option['decorated_is_last'];
        } elseif ($option instanceof Varien_Object) {
            $isLast = @$option->getDecoratedIsLast();
        }
        if ($this->getRequired() && $isLast) {
            $this->addClass('udvalidate-radios');
        } else {
            $this->removeClass('required-entry');
        }
        $html = '<input type="radio"'.$this->serialize(array('name', 'class', 'style'));
        if (is_array($option)) {
            $html.= 'value="'.$this->_escape($option['value']).'"  id="'.$this->getHtmlId().$option['value'].'"';
            if ($option['value'] == $selected) {
                $html.= ' checked="checked"';
            }
            $html.= ' />';
            $html.= '<label class="inline" for="'.$this->getHtmlId().$option['value'].'">'.$option['label'].'</label>';
        }
        elseif ($option instanceof Varien_Object) {
            $html.= 'id="'.$this->getHtmlId().$option->getValue().'"'.$option->serialize(array('label', 'title', 'value', 'class', 'style'));
            if (in_array($option->getValue(), $selected)) {
                $html.= ' checked="checked"';
            }
            $html.= ' />';
            $html.= '<label class="inline" for="'.$this->getHtmlId().$option->getValue().'">'.$option->getLabel().'</label>';
        }
        $html.= $this->getSeparator() . "\n";
        return $html;
    }
}