<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Common_Block_Adminhtml_System_Config_Suppressed
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    protected $_suppressed = array();

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('bronto/common/suppressed.phtml');
    }

    /**
     * Prepare the layout
     *
     * @return Bronto_Common_Block_Adminhtml_System_Config_Cron
     */
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $html = $this->_getHeaderHtml($element);

        foreach ($element->getSortedElements() as $elem) {
            $html .= $elem->toHtml();
        }

        $html .= $this->toHtml();
        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * Get URL for AJAX call
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return '';
    }

    /**
     * Get URL for reset AJAX call
     *
     * @return string
     */
    public function getResetUrl()
    {
        return '';
    }

    /**
     * The Suppression interface is always collapsed
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return bool
     */
    protected function _getCollapseState($element)
    {
        return false;
    }
}
