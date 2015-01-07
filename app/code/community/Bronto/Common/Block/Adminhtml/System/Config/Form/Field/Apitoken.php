<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Common_Block_Adminhtml_System_Config_Form_Field_Apitoken
    extends Mage_Adminhtml_Block_System_Config_Form_Field
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
        // Only do validation if module is installed and active
        if ($this->helper('bronto_common')->isModuleInstalled('Bronto_Verify')) {
            $_html = array();

            // Create form object to grab scope details
            $form    = new Mage_Adminhtml_Block_System_Config_Form;
            $scope   = $form->getScope();
            $scopeId = $form->getScopeId();

            $element->setData('onchange', "validateToken(this.form, this);");
            $element->setData('after_element_html', "
                <span id=\"loadingMask\" style=\"display: none; width: 100px;\">
                    <span class=\"loader\" id=\"loading-mask-loader\" style=\"background: url(" .
                $this->getSkinUrl('bronto/images/ajax-loader-tr.gif') .
                ") no-repeat 0 50%; background-size: 20px; padding:3px 0 3px 25px;\">" .
                $this->__(' Verifying...') .
                "</span>
                    <span id=\"loading-mask\"></span>
                </span>
                <script>
                    /**
                     * Function to Toggle Form Elements Disabled Status Based On Token Status
                     */
                    function toggleDisabled(form, element) {
                        // Get Status Text Element
                        var statusText = $('bronto-validation-status-text');
                        // If Status Text Element has Class of 'invalid' or empty, set boolean disabled value
                        var disabled = (statusText.className == 'invalid' || statusText.className == '');

                        // Cycle through form elements and disable/enable elements
                        for (i = 0; i < form.length; i++) {
                            if (form.elements[i].id != '{$element->getId()}' &&
                                form.elements[i].id != 'bronto_settings_enabled' &&
                                form.elements[i].id != 'verify-button' &&
                                form.elements[i].type != 'hidden' &&
                                form.elements[i].name.indexOf('groups') == 0) {
                                form.elements[i].disabled = disabled;
                            }
                        }

                        // Get Last Element of Form, and if the class name is 'note', empty the html value
                        var last = element.parentNode.lastChild;
                        if (last.className == 'note') {
                            last.innerHTML = '';
                        }
                    }

                    function trim1 (str) {
                        return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
                    }

                    function validateToken(form, element) {
                        var token      = trim1($('{$element->getId()}').value);
                        var statusText = $('bronto-validation-status');
                        var reloadUrl  = '{$this->getUrl('*/apitoken/ajaxvalidation')}';

                        statusText.innerHTML = $('loadingMask').innerHTML;
                        statusText.removeClassName('valid').removeClassName('invalid');

                        new Ajax.Request(reloadUrl, {
                            method: 'post',
                            parameters: {token: token, scope: '{$scope}', scopeid: '{$scopeId}'},
                            onComplete: function(transport) {
                                Element.hide('loadingMask');
                                statusText.innerHTML = transport.responseText;

                                toggleDisabled(form, element);
                            }
                        });

                        return false;
                    }
                </script>
            ");

            if (!$this->helper('bronto_common')->getApiToken()) {
                $element->setComment(
                    '<span style="color:red;font-weight:bold">Please enter your Bronto API key here.</span>'
                );
                $buttonHtml = "";
            } else {
                $button = $this->getLayout()
                    ->createBlock('bronto_verify/adminhtml_widget_button_runtoken')
                    ->toHtml();

                $buttonHtml = "<p class=\"form-buttons\" id=\"verify-button\">{$button}</p>";
            }

            // Show Roundtrip Install Verification Status
            $_html[] = $buttonHtml .
                '<style>' .
                '   #bronto-validation-status { color:grey; font-weight:bold; }' .
                '   #bronto-validation-status .valid { color: green; }' .
                '   #bronto-validation-status .invalid { color: red; }' .
                '</style>' . '<strong style="float: left; width: 80px">Last Status:</strong> ' .
                '<span id="bronto-validation-status">' .
                $this->helper('bronto_verify/apitoken')->getAdminScopedApitokenStatusText() .
                '</span>';

            // Show everything Else
            if (!empty($_html)) {
                $elementHtml = $element->getElementHtml();
                if ($element->getComment()) {
                    $elementHtml .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
                    $element->setComment(null);
                }
                $elementHtml .= '<div style="margin-top:10px">';
                $elementHtml .= implode('<br />', $_html);
                $elementHtml .= '</div>';

                return $elementHtml;
            }
        }

        return parent::_getElementHtml($element);
    }
}
