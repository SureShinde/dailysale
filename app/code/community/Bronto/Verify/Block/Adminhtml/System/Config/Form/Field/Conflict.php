<?php

/**
 * Conflict Checker Status Field
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Block_Adminhtml_System_Config_Form_Field_Conflict extends Mage_Adminhtml_Block_System_Config_Form_Field
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

        $script = "
            <span id=\"conflict-loadingmask\" style=\"display: none; width: 100px;\">
                <span class=\"loader\" id=\"conflict-loading-mask-loader\" style=\"background: url(" . $this->getSkinUrl('bronto/images/ajax-loader-tr.gif') . ") no-repeat 0 50%; background-size: 20px; padding:3px 0 3px 25px;\">" . $this->__(' Checking Conflicts...') . "</span>
                <span id=\"conflict-loading-mask\"></span>
            </span>
            <script>
                Event.observe(window, 'load', function() { 
                    var parentTBody = $('bronto_verify_conflictchecker');
                    
                    var newTr = '<tr id=\"bronto_verify_conflictchecker_errors\"><td class=\"conflicts_errors\" colspan=\"4\"><div id=\"bronto-conflicts\"></div></td></tr>';
                    parentTBody.innerHTML = parentTBody.innerHTML + newTr;
                });
                function checkConflicts() {
                    var reloadurl  = '{$this->getUrl('*/conflictchecker/ajaxvalidation')}';
                    var statusText = $('bronto-conflicts');
                    
                    statusText.innerHTML = $('conflict-loadingmask').innerHTML;

                    new Ajax.Request(reloadurl, {
                        method: 'post',
                        onComplete: function(transport) {
                            Element.hide('conflict-loadingmask');
                            statusText.innerHTML = transport.responseText;
                        }
                    });
                    
                    return false;
                }
            </script>
        ";


        $button     = $this->getLayout()
            ->createBlock('bronto_verify/adminhtml_widget_button_conflict')
            ->toHtml();
        $buttonHtml = "<p class=\"form-buttons\" id=\"verify-button\" style=\"float:none;\">{$button}</p>";


        // Show Roundtrip Install Verification Status
        $_html[] = $buttonHtml;
        $_html[] = $script;

        // Show everything Else
        if (!empty($_html)) {
            $elementHtml = implode('', $_html);

            return $elementHtml;
        }

        return parent::_getElementHtml($element);
    }
}
