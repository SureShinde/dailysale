<?php

class Bronto_Common_Block_Adminhtml_System_Config_Form_Field_Support_Site extends Bronto_Common_Block_Adminhtml_System_Config_Form_Field_Support
{
    /**
     * Override for disabling support information until API token is set
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $brontoLink = '<a href="http://app.bronto.com" target="_blank"'
            . 'title="Home Dashboard |&nbsp;Bronto Marketing Platform">Bronto</a>';
        $helpIcon   = $this->getSkinUrl('bronto/images/site_name.png');
        $comment    = 'Found within '
            . $brontoLink . ', located at:'
            . '<div class="bronto-with-help">'
            . '<strong>Home &raquo; Settings &raquo; General Settings</strong>'
            . '<div class="bronto-help bronto-vertical-align">'
            . '<div class="bronto-help-window bronto-large-image">'
            . '<img src="' . $helpIcon . '" width="640px"/></div></div></div>';

        $element->setComment($comment);

        return parent::_getElementHtml($element);
    }
}
