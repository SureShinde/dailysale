<?php

class Bronto_Common_Block_Adminhtml_System_Config_Form_Field_Image extends Bronto_Common_Block_Adminhtml_System_Config_Form_Field
{

    /**
     * Adds some Javascript to the drop-down to place recommended defaults
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $defaultSizes = array(
            'image'       => array('width' => '265', 'height' => ''),
            'small_image' => array('width' => '88', 'height' => '77'),
            'thumbnail'   => array('width' => '75', 'height' => '75'),
        );

        $element->setData('onchange', "defaultImageSize();");
        $element->setData('after_element_html', "
			<script>
				function defaultImageSize() {
					var sizes = " . json_encode($defaultSizes) . ";
					var size = sizes[$('{$element->getId()}').value];
					if (size) {
						for (var name in size) {
							$('bronto_format_image_' + name).value = size[name];
						}
					}
				}
			</script>
		");

        return parent::_getElementHtml($element);
    }
}
