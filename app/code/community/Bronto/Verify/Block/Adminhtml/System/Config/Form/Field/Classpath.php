<?php

/**
 * Class Path Search Field
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Block_Adminhtml_System_Config_Form_Field_Classpath extends Mage_Adminhtml_Block_System_Config_Form_Field
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
            <span id=\"classpath-loadingmask\" style=\"display: none; width: 100px;\">
                <span class=\"loader\" id=\"classpath-loading-mask-loader\" style=\"background: url(" . $this->getSkinUrl('bronto/images/ajax-loader-tr.gif') . ") no-repeat 0 50%; background-size: 20px; padding:3px 0 3px 25px;\">" . $this->__(' Searching For Class Path...') . "</span>
                <span id=\"classpath-loading-mask\"></span>
            </span>

            <script>
            //<![CDATA[
                Event.observe(window, 'load', function() {
                    var newTr = '<tr id=\"bronto_verify_advanced_classpath_results\"><td class=\"classpath_results\" colspan=\"4\"><div id=\"bronto-classpath-results\"></div></td></tr>';
                    $('" . $this->_getRowElementId($element) . "').insert({after: newTr});
                });
                function searchClasspath() {
                    var reloadUrl  = '{$this->getUrl('*/advanced/ajaxclasspath')}';
                    var statusText = $('bronto-classpath-results');

                    var searchText = $('bronto_verify_advanced_classpath_search').value;
                    var classType  = $('classpath-type').value;

                    statusText.innerHTML = $('classpath-loadingmask').innerHTML;

                    new Ajax.Request(reloadUrl, {
                        method: 'post',
                        parameters: {class: searchText, type: classType},
                        onComplete: function(transport) {
                            Element.hide('classpath-loadingmask');
                            statusText.innerHTML = transport.responseText;
                        }
                    });

                    return false;
                }
            //]]>
            </script>
        ");

        // Add Class Type Selector to Input
        $classTypeHtml = '<select id="classpath-type"><option value="model">Model (Default)</option><option value="helper">Helper</option><option value="block">Block</option><option value="controller">Controller</option></select>';

        // Add Search Button
        $button     = $this->getLayout()
            ->createBlock('bronto_verify/adminhtml_widget_button_classpath')
            ->toHtml();
        $buttonHtml = "<p class=\"form-buttons\" id=\"events-button\" style=\"float:none;\">{$button}</p>";


        // Add Button to Html
        $_html[] = $buttonHtml;

        // Show everything Else
        if (!empty($_html)) {
            $elementHtml = $element->getElementHtml();
            $elementHtml .= $classTypeHtml;
            if ($element->getComment()) {
                $elementHtml .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
                $element->setComment(null);
            }
            $elementHtml .= '<div style="margin-top:10px">';
            $elementHtml .= implode('<br />', $_html);
            $elementHtml .= '</div>';

            return $elementHtml;
        }

        return parent::_getElementHtml($element);
    }
}
