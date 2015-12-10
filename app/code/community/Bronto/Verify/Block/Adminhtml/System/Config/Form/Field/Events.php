<?php

/**
 * Event Search Field
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Block_Adminhtml_System_Config_Form_Field_Events extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Get element ID of the dependent field's parent row
     *
     * @param object $element
     *
     * @return String
     */
    protected function _getRowElementId($element)
    {
        return 'row_' . $element->getId();
    }

    /**
     * Override method to output our custom HTML with JavaScript
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return String
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $_html = array();

        $element->setData('after_element_html', "
            <span id=\"observer-loadingmask\" style=\"display: none; width: 100px;\">
                <span class=\"loader\" id=\"observer-loading-mask-loader\" style=\"background: url(" . $this->getSkinUrl('bronto/images/ajax-loader-tr.gif') . ") no-repeat 0 50%; background-size: 20px; padding:3px 0 3px 25px;\">" . $this->__(' Searching For Observers...') . "</span>
                <span id=\"observer-loading-mask\"></span>
            </span>

            <script>
            //<![CDATA[
                Event.observe(window, 'load', function() {
                    var newTr = '<tr id=\"bronto_verify_advanced_results\"><td class=\"observer_results\" colspan=\"4\"><div id=\"bronto-observer-results\"></div></td></tr>';
                    $('" . $this->_getRowElementId($element) . "').insert({after: newTr});
                });
                function searchObservers() {
                    var reloadUrl  = '{$this->getUrl('*/advanced/ajaxobservers')}';
                    var statusText = $('bronto-observer-results');

                    var searchText = $('bronto_verify_advanced_observer_search').value;

                    statusText.innerHTML = $('observer-loadingmask').innerHTML;

                    new Ajax.Request(reloadUrl, {
                        method: 'post',
                        parameters: {event: searchText},
                        onComplete: function(transport) {
                            Element.hide('observer-loadingmask');
                            statusText.innerHTML = transport.responseText;
                        }
                    });

                    return false;
                }
            //]]>
            </script>
        ");


        $button     = $this->getLayout()
            ->createBlock('bronto_verify/adminhtml_widget_button_events')
            ->toHtml();
        $buttonHtml = "<p class=\"form-buttons\" id=\"events-button\" style=\"float:none;\">{$button}</p>";


        // Add Button to Html
        $_html[] = $buttonHtml;

        // Show everything Else
        if (!empty($_html)) {
            $elementHtml = $element->getElementHtml();
            if ($element->getComment()) {
                $elementHtml .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
                $element->setComment(null);
            }
            $elementHtml .= '<div style="margin-top:10px">';
            $elementHtml .= implode('', $_html);
            $elementHtml .= '</div>';

            return $elementHtml;
        }

        return parent::_getElementHtml($element);
    }
}
