<?php
class Bronto_Verify_Block_Adminhtml_System_Config_Form_Field_Button
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function _prepareLayout()
    {
        parent::_construct();
        if (!$this->getTemplate()) {
            $this->setTemplate('bronto/verify/permissionchecker/button.phtml');
        }

        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element Form element
     *
     * @return string
     * @access public
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(array(
            'button_label' => Mage::helper('bronto_verify')->__($originalData['button_label']),
            'html_id'      => $element->getHtmlId(),
            'ajax_url'     => Mage::getSingleton('adminhtml/url')->getUrl('*/permissionchecker/verify')
        ));

        return $this->_toHtml();
    }
}
