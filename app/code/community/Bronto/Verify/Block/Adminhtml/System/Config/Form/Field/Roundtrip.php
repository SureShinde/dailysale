<?php

/**
 * API Token Status Field
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Block_Adminhtml_System_Config_Form_Field_Roundtrip extends Mage_Adminhtml_Block_System_Config_Form_Field
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

        // Create form object to grab scope details
        $form    = new Mage_Adminhtml_Block_System_Config_Form;
        $scope   = $form->getScope();
        $scopeId = $form->getScopeId();

        $script = "
            <span id=\"roundtrip_loadingmask\" style=\"display: none; width: 100px;\">
                <span class=\"loader\" id=\"roundtrip-loading-mask-loader\" style=\"background: url(" . $this->getSkinUrl('bronto/images/ajax-loader-tr.gif') . ") no-repeat 0 50%; background-size: 20px; padding:3px 0 3px 25px;\">" . $this->__(' Validating...') . "</span>
                <span id=\"roundtrip-loading-mask\"></span>
            </span>
            <script>
                function validateRoundtrip() {
                    var statusText = $('bronto-roundtrip-status');
                    var reloadurl  = '{$this->getUrl('*/roundtrip/ajaxvalidation')}';
                    
                    statusText.innerHTML = $('roundtrip_loadingmask').innerHTML;
                    statusText.removeClassName('valid').removeClassName('invalid');

                    new Ajax.Request(reloadurl, {
                        method: 'post',
                        parameters: {scope: '{$scope}', scopeid: '{$scopeId}'},
                        onComplete: function(transport) {
                            Element.hide('roundtrip_loadingmask');
                            if (transport.responseText == '\"Passed Verification\"') {
                                statusText.innerHTML = 'Passed Verification';
                                statusText.addClassName('valid');
                            } else if (transport.responseText == '\"Failed Verification\"') {
                                statusText.innerHTML = 'Failed Verification';
                                statusText.addClassName('invalid');
                            } else {
                                statusText.innerHTML = 'Failed Verification';
                                statusText.addClassName('invalid');
                            }
                        }
                    });
                    
                    return false;
                }
            </script>
        ";


        $statusText = '<span style="color:grey;font-weight:bold">' .
            $this->helper('bronto_verify/roundtrip')->getAdminScopedRoundtripStatusText() .
            '</span>';
        $button     = $this->getLayout()
            ->createBlock('bronto_verify/adminhtml_widget_button_runroundtrip')
            ->toHtml();
        $buttonHtml = "<p class=\"form-buttons\" id=\"roundtrip-verify-button\">{$button}</p>";

        // Show Roundtrip Install Verification Status
        $_html[] = '<style>' .
            '#bronto-roundtrip-status { color:grey; font-weight:bold; }' .
            '#bronto-roundtrip-status.valid { color: green; }' .
            '#bronto-roundtrip-status.invalid { color: red; }' .
            '</style>' . $statusText . $buttonHtml;

        $_html[] = $script;

        // Show everything Else
        if (!empty($_html)) {
            $elementHtml = implode('<br />', $_html);

            return $elementHtml;
        }

        return parent::_getElementHtml($element);
    }
}
