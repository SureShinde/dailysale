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
class Bronto_Verify_Block_Adminhtml_System_Config_Form_Field_Apitoken
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
        $_html = array();

        // Create form object to grab scope details
        $form    = new Mage_Adminhtml_Block_System_Config_Form;
        $scope   = $form->getScope();
        $scopeId = $form->getScopeId();
        $token   = Mage::helper('bronto_verify/apitoken')->getApiToken();

        $script = "
            <span id=\"loadingmask\" style=\"display: none; width: 100px;\">
                <span class=\"loader\" id=\"loading-mask-loader\" style=\"background: url(" .
            $this->getSkinUrl('bronto/images/ajax-loader-tr.gif') .
            ") no-repeat 0 50%; background-size: 20px; padding:3px 0 3px 25px;\">" .
            $this->__(' Validating...') .
            "</span>
                <span id=\"loading-mask\"></span>
            </span>
            <script>
                function trim1 (str) {
                    return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
                }

                function validateToken() {
                    var token      = trim1('{$token}');
                    var statusText = $('bronto-validation-status');
                    var reloadurl  = '{$this->getUrl('*/apitoken/ajaxvalidation')}';
                    
                    statusText.innerHTML = $('loadingmask').innerHTML;
                    statusText.removeClassName('valid').removeClassName('invalid');

                    new Ajax.Request(reloadurl, {
                        method: 'post',
                        parameters: {token: token, scope: '{$scope}', scopeid: '{$scopeId}'},
                        onComplete: function(transport) {
                            Element.hide('loadingmask');
                            statusText.innerHTML = transport.responseText;
                        }
                    });
                    
                    return false;
                }
            </script>
        ";

        $button     = $this->getLayout()
            ->createBlock('bronto_verify/adminhtml_widget_button_runtoken')
            ->toHtml();
        $buttonHtml = "<p class=\"form-buttons\" id=\"verify-button\">{$button}</p>";

        // Show API Token Verification Status
        $_html[] = $buttonHtml .
            '<style>' .
            '   #bronto-validation-status { color:grey; font-weight:bold; }' .
            '   #bronto-validation-status .valid { color: green; }' .
            '   #bronto-validation-status .invalid { color: red; }' .
            '</style>' .
            '<span id="bronto-validation-status">' .
            $this->helper('bronto_verify/apitoken')->setApiToken($token)->getAdminScopedApitokenStatusText() .
            '</span>';

        $_html[] = $script;

        // Show everything Else
        if (!empty($_html)) {
            $elementHtml = implode('<br />', $_html);

            return $elementHtml;
        }

        return parent::_getElementHtml($element);
    }
}
