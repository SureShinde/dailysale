<?php

class Bronto_Common_Block_Adminhtml_System_Config_Suppressed_Reset extends Bronto_Common_Block_Adminhtml_Widget_Button_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * @see parent
     */
    protected function _setUp()
    {
        $this->setOnClick('resetAllSuppressed(); return false;');
        $this->setClass('delete');
        $this->setStyle('margin-bottom: 10px');
    }

    /**
     * @see parent
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setLabel($element->getLabel());
        return $this->toHtml();
    }
}
