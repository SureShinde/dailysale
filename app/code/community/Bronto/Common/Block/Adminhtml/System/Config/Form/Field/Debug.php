<?php

class Bronto_Common_Block_Adminhtml_System_Config_Form_Field_Debug extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Generate the button and surround html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getButtonHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $script = '
            <p class="note" style="margin-top: 3px; width:550px"><strong>Disclaimer:</strong> Only share with Bronto Support through an actual Bronto support case and not on any public forums.</p>
            <span id="debug-loadingmask" style="display:none; width: 100px;">
                <span class="loader" id="debug-loading-mask-loader" style="background: url(' . $this->getSkinUrl('bronto/images/ajax-loader-tr.gif') . ') no-repeat 0 50%; background-size: 20px; padding: 3px 0 3px 25px;">' . $this->__('Collecting Information') . '</span>
            </span>
            <div id="debug-information-result" style="display:none;margin: 13px 3px 3px 0"></div>
            <script>
                function collectDebugInformation() {
                    var collectUrl = "' . $this->getUrl("*/debug/collect") . '";
                    var debugResult = $("debug-information-result");
                    debugResult.innerHTML = $("debug-loadingmask").innerHTML;
                    Element.show(debugResult);

                    new Ajax.Request(collectUrl, {
                        method: "post",
                        onComplete: function(transport) {
                            debugResult.innerHTML = "<textarea readonly style=\"width: 480px; height:480px; resize:none;\">" + JSON.stringify(transport.responseJSON, undefined, 4) + "</textarea>";
                        }
                    });
                }
            </script>
        ';

        $button = $this->getLayout()->createBlock('bronto_common/adminhtml_widget_button_debug');

        return $button->toHtml() . $script;
    }

    /**
     * Empty the element html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return '';
    }

    /**
     * Remove the "scope" value
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        $element->setLabel($this->_getButtonHtml($element));

        return parent::render($element);
    }
}
