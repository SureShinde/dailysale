<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Customer_Model_System_Config_Backend_Brontofield extends Mage_Core_Model_Config_Data
{
    /**
     * Processing object before save data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        //if ($this->isValueChanged()) {
        if ($this->field == 'reward_points' || $this->field == 'store_credit') {
            Mage::throwException($this->getValue());
        }

        /* @var $fieldObject Bronto_Api_Field */
        $fieldObject = Mage::getModel('bronto_common/system_config_source_field')->getFieldObjectById($this->getValue());

        if ($fieldObject) {
            $attributes = null;
            if ('attributes' == $this->group_id) {
                $attributes = Mage::getModel('customer/entity_attribute_collection');
            } elseif ('address_attributes' == $this->group_id) {
                $attributes = Mage::getModel('customer/entity_address_attribute_collection')->addVisibleFilter();
            }

            if ($attributes) {
                foreach ($attributes as $attribute) {
                    if ($this->field == $attribute->attribute_code) {
                        if ($attribute->frontend_input != $fieldObject->type && $fieldObject->type != 'text') {
                            $message = "Input type doesn't match: {$this->getFieldConfig()->label} [type: {$attribute->frontend_input}] => {$fieldObject->label} [type: {$fieldObject->type}]"
                                . "... Please note that this could cause issues when attempting to import customers";
                            // Throw Warning, but allow saving
                            Mage::getSingleton('core/session')->addWarning(Mage::helper('adminhtml')->__($message));
                            // Throw Exception and prevent saving
                            // Mage::throwException(Mage::helper('adminhtml')->__($message));
                        }
                    }
                }
            }
        }

        //}

        return parent::_beforeSave();
    }

    /**
     * @param $path
     * @param $value
     *
     * @return $this
     */
    protected function _saveConfigData($path, $value)
    {
        Mage::getModel('core/config_data')
            ->load($path, 'path')
            ->setValue($value)
            ->setPath($path)
            ->save();

        return $this;
    }
}
