<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Customer_Model_System_Config_Backend_Newfield extends Mage_Core_Model_Config_Data
{
    private static $_fieldCache = array();

    /**
     * Processing object before save data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        if ($this->isValueChanged()) {
            try {
                $fieldObject = Mage::helper('bronto_common')->getApi()->transferField();
                $fieldName = Bronto_Utils::normalize($this->getValue());
                if (!array_key_exists($fieldName, self::$_fieldCache)) {
                    $field = $fieldObject->getByName($fieldName);
                    if (!$field) {
                        $field->withName($fieldName)->withLabel($this->getValue())->asText()->asHidden();
                        $field->withId($fieldObject->add()->addField($field)->first()->getItem()->getId());
                    }
                    self::$_fieldCache[$fieldName] = $field;
                }
                $this->_saveConfigData(str_replace('_new', '', $this->getPath()), $field->getId());
                $this->setValue(null);
            } catch (Exception $e) {
                Mage::throwException(Mage::helper('adminhtml')->__('Unable to save new field: ') . $e->getMessage());
            }
        }

        return parent::_beforeSave();
    }

    /**
     * Save Configuration Data
     *
     * @param $path
     * @param $value
     *
     * @return $this
     */
    protected function _saveConfigData($path, $value)
    {
        $scopeParams = Mage::helper('bronto_common')->getScopeParams();

        $scope = $scopeParams['scope'];
        if ($scope != 'default') {
            $scope .= 's';
        }

        Mage::getModel('core/config_data')
            ->load($path, 'path')
            ->setValue($value)
            ->setPath($path)
            ->setScope($scope)
            ->setScopeId($scopeParams[$scopeParams['scope'] . '_id'])
            ->save();

        return $this;
    }
}
