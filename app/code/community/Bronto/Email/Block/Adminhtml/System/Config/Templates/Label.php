<?php

/**
 * @package   Bronto\Email
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Config_Templates_Label
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Heading
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $label = $element->getLabel();

        preg_match('/##[a-zA-Z-_]*##/', $label, $matches);
        if (isset($matches[0])) {
            $match   = $matches[0];
            $section = str_replace('#', '', $match);
            $label   = str_replace($match, '', $label);

            $sectionUrl = Mage::helper('bronto_email')->getScopeUrl('/system_config/edit/section/' . $section);
            $labelParts = explode('&raquo;', $label);
            $newLabel   = trim($labelParts[0]) .
                " &raquo; <a href=\"{$sectionUrl}\" title=\"{$label}\"><strong>" .
                trim($labelParts[1]) .
                "</strong></a> &raquo; " .
                trim($labelParts[2]);

            $element->setLabel($newLabel);
        }

        return parent::render($element);
    }
}
