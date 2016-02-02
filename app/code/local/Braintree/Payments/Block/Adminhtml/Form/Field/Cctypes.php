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

class Braintree_Payments_Block_Adminhtml_Form_Field_Cctypes extends Mage_Core_Block_Html_Select
{
    /**
     * All possible credit card types
     * 
     * @var array
     */
    protected $_ccTypes = array();
    
    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getCcTypes() as $country) {
                if (isset($country['value']) && $country['value'] && isset($country['label']) && $country['label']) {
                    $this->addOption($country['value'], $country['label']);
                }
            }
        }
        $this->setExtraParams('multiple="multiple" style="height:80px;"');
        return parent::_toHtml();
    }

    /**
     * All possible credit card types
     * 
     * @return array
     */
    protected function _getCcTypes()
    {
        if (!$this->_ccTypes) {
            $this->_ccTypes = Mage::getModel('braintree_payments/source_cctype')->toOptionArray();
        }
        return $this->_ccTypes;
    }

    /**
     * Sets name for input element
     * 
     * @param string $value
     * @return Braintree_Payments_Block_Adminhtml_Form_Field_Cctypes
     */
    public function setInputName($value)
    {
        return $this->setName($value . '[]');
    }
}
