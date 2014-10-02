<?php

class TM_NewsletterBooster_Block_Adminhtml_Dashboard_Campaign_Switcher_Form_Renderer_Fieldset
    extends Mage_Adminhtml_Block_Template implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Form element which re-rendering
     *
     * @var Varien_Data_Form_Element_Fieldset
     */
    protected $_element;

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->setTemplate('newsletterbooster/dashboard/campaign/switcher/form/renderer/fieldset.phtml');
    }

    /**
     * Retrieve an element
     *
     * @return Varien_Data_Form_Element_Fieldset
     */
    public function getElement()
    {
        return $this->_element;
    }

    /**
     * Render element
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_element = $element;
        return $this->toHtml();
    }

    /**
     * Return html for store switcher hint
     *
     * @return string
     */
    public function getHintHtml()
    {
        return Mage::getBlockSingleton('adminhtml/store_switcher')->getHintHtml();
    }
}
