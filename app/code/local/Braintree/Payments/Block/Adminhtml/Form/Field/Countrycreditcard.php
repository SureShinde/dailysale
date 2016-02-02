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

class Braintree_Payments_Block_Adminhtml_Form_Field_Countrycreditcard 
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_countryRenderer = null;
    protected $_ccTypesRenderer = null;
    
    /**
     * Returns renderer for country element
     * 
     * @return Braintree_Payments_Block_Adminhtml_Form_Field_Countries
     */
    protected function _getCountryRenderer()
    {
        if (!$this->_countryRenderer) {
            $this->_countryRenderer = $this->getLayout()->createBlock(
                'braintree_payments/adminhtml_form_field_countries',
                '',
                array('is_render_to_js_template' => true)
            );            
        }
        return $this->_countryRenderer;
    }

    /**
     * Returns renderer for country element
     * 
     * @return Braintree_Payments_Block_Adminhtml_Form_Field_Countries
     */
    protected function _getCcTypesRenderer()
    {
        if (!$this->_ccTypesRenderer) {
            $this->_ccTypesRenderer = $this->getLayout()->createBlock(
                'braintree_payments/adminhtml_form_field_cctypes',
                '',
                array('is_render_to_js_template' => true)
            );
        }
        return $this->_ccTypesRenderer;
    }

    /**
     * Prepare to render
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'country_id',
            array(
                'label'     => Mage::helper('braintree_payments')->__('Country'),
                'renderer'  => $this->_getCountryRenderer(),
            )
        );
        $this->addColumn(
            'cc_types',
            array(
                'label' => Mage::helper('braintree_payments')->__('Allowed Credit Card Types'),
                'renderer'  => $this->_getCcTypesRenderer(),
            )
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('braintree_payments')->__('Add Rule');
    }

    /**
     * Prepare existing row data object
     *
     * @param Varien_Object
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $data = $row->getData();
        $country = isset($data['_id']) ? $data['_id'] : false;
        if ($country) {
            unset($data['_id']);
            $row->setData(
                'option_extra_attr_' . $this->_getCountryRenderer()->calcOptionHash($country), 'selected="selected"'
            );
            
            foreach ($data as $cardType) {
                $row->setData(
                    'option_extra_attr_' . $this->_getCcTypesRenderer()->calcOptionHash($cardType),
                    'selected="selected"'
                );
            }
        }
    }
}
