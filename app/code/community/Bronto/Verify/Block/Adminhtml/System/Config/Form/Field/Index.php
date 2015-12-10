<?php

class Bronto_Verify_Block_Adminhtml_System_Config_Form_Field_Index
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('bronto/verify/permissionchecker/index.phtml');
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element Form element
     *
     * @return string
     * @access public
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }
}
