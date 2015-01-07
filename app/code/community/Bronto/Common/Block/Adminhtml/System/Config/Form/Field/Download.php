<?php

class Bronto_Common_Block_Adminhtml_System_Config_Form_Field_Download extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getButtonHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $script = '
            <span id="archive-loadingmask" style="display:none; width: 100px;">
                <span class="loader" id="archive-loading-mask-loader" style="background: url(' . $this->getSkinUrl('bronto/images/ajax-loader-tr.gif') . ') no-repeat 0 50%; background-size: 20px; padding: 3px 0 3px 25px;">' . $this->__('Creating Archive') . '</span>
            </span>
            <div id="archive-information-result" style="display:none;margin:13px 3px 0 0"></div>
            <script>
                function createLogArchive() {
                    var archiveUrl = "' . $this->getUrl("*/debug/archive") . '";
                    var archiveResult = $("archive-information-result");
                    archiveResult.innerHTML = $("archive-loadingmask").innerHTML;
                    Element.show(archiveResult);

                    new Ajax.Request(archiveUrl, {
                        method: "post",
                        onComplete: function(transport) {
                            var response = transport.responseJSON;
                            archiveResult.innerHTML = "<p>Created archive: <a target=\"_blank\" href=\"" + response.link + "\">" + response.name + "</a></p>";
                        }
                    });
                }
            </script>
        ';

        $button = $this->getLayout()->createBlock('bronto_common/adminhtml_widget_button_download');

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
