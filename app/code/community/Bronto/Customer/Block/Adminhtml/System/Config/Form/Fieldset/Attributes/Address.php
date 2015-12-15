<?php

/**
 * @package   Bronto\Customer
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Customer_Block_Adminhtml_System_Config_Form_Fieldset_Attributes_Address extends Bronto_Customer_Block_Adminhtml_System_Config_Form_Fieldset_Attributes
{
    /**
     * @var array<Mage_Customer_Model_Attribute>
     */
    private $_addressAttributes;

    /**
     * @var array<string>
     */
    protected $_ignoreAttributes = array(
        'firstname',
        'lastname',
        'middlename',
        'prefix',
        'region_id',
        'suffix',
        'vat_id',
        'vat_is_valid',
        'vat_request_id',
        'vat_request_date',
        'vat_request_success',
    );

    protected $_configPath = Bronto_Customer_Helper_Data::XML_PREFIX_ADDRESS_ATTR;
    protected $_idPath = 'bronto_customer_address_attributes_';
    protected $_fieldNameTemplate = 'groups[address_attributes][fields][_attrCode_][value]';

    /**
     * @return array
     */
    protected function _getAttributes()
    {
        return $this->_getAddressAttributes();
    }

    /**
     * @return array
     */
    private function _getAddressAttributes()
    {
        if ($this->_addressAttributes === null) {
            $this->_addressAttributes = Mage::getModel('customer/entity_address_attribute_collection');
        }

        return $this->_addressAttributes;
    }
}
