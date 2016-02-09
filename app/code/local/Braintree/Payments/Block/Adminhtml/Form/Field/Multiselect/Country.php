<?php
/**
* Braintree Payments Extension
*
* This source file is subject to the Braintree Payment System Agreement (https://www.braintreepayments.com/legal)
*
* DISCLAIMER
* This file will not be supported if it is modified.
*
* @copyright   Copyright (c) 2015 Braintree. (https://www.braintreepayments.com/)
*/

class Braintree_Payments_Block_Adminhtml_Form_Field_Multiselect_Country 
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Adding custom JS to element HTML
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $values = $element->getValue();
        if ($values) {
            $values = '"' . str_replace(',', '","', $values) . '"';
        }
        $script = "
            <script type=\"text/javascript\">
                Array.prototype.diff = function(a) {
                    return this.filter(function(i) {return a.indexOf(i) < 0;});
                };
                var el = '{$element->getHtmlId()}';
                var previousValues = [$values];
                Event.observe(el, 'change', function(){
                    var options = $(el).options;
                    var selectedOptions = [];
                    for (var i=0; i < options.length; i++) {
                        if (options[i].selected) {
                            selectedOptions.push(options[i].value);
                        }
                    }
                    var threeDSElement = el.replace('specificcountry', 'three_d_secure_specificcountry');
                    if ($(threeDSElement)) {
                        var optionsToSelect = selectedOptions.diff(previousValues);
                        var optionsToDeselect = previousValues.diff(selectedOptions);
                        for (var i=0; i < optionsToSelect.length; i++) {
                            var option = $$('#' + threeDSElement + ' option[value=' + optionsToSelect[i] +']');
                            if (typeof option[0] != 'undefined') {
                                option[0].selected = true;
                            }
                        }
                        for (var i=0; i < optionsToDeselect.length; i++) {
                            var option = $$('#' + threeDSElement + ' option[value=' + optionsToDeselect[i] +']');
                            if (typeof option[0] != 'undefined') {
                                option[0].selected = false;
                            }
                        }
                    }
                    previousValues = selectedOptions;
                });
            </script>";
        return parent::_getElementHtml($element) . $script;
    }
}
