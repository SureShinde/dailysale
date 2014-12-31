<?php

class Buysafe_Siteseals_Block_Image 
	extends Mage_Adminhtml_Block_Abstract 
	implements Varien_Data_Form_Element_Renderer_Interface
{
    public function render(Varien_Data_Form_Element_Abstract $fieldset)
    {
        return $fieldset->getOriginalData('label');
    }

}
