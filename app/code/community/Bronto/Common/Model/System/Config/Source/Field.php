<?php

/**
 * @category Bronto
 * @package  Common
 */
class Bronto_Common_Model_System_Config_Source_Field
{
    /**
     * @var array
     */
    protected $_options = array();

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!empty($this->_options)) {
            return $this->_options;
        }

        try {
            if ($api = Mage::helper('bronto_common')->getApi()) {
                /* @var $fieldObject Bronto_Api_Field */
                $fieldObject = $api->getFieldObject();
                foreach ($fieldObject->readAll()->iterate() as $field/* @var $field Bronto_Api_Field_Row */) {
                    $this->_options[] = array(
                        'value' => $field->id,
                        'label' => $field->label,
                    );
                }
            }
        } catch (Exception $e) {
            Mage::helper('bronto_common')->writeError($e);
        }

        array_unshift($this->_options, array(
            'label' => 'Create New...',
            'value' => '_new_',
        ));

        array_unshift($this->_options, array(
            'label' => '-- None Selected --',
            'value' => '_none_',
        ));

        return $this->_options;
    }

    /**
     * Get Field Object by ID
     *
     * @param string $id
     *
     * @return boolean|Bronto_Api_Field_Row
     */
    public function getFieldObjectById($id)
    {
        try {
            if ($api = Mage::helper('bronto_common')->getApi()) {
                /* @var $fieldObject Bronto_Api_Field */
                $fieldObject = $api->getFieldObject();
                foreach ($fieldObject->readAll()->iterate() as $field/* @var $field Bronto_Api_Field_Row */) {
                    if ($field->id == $id) {
                        return $field;
                    }
                }
            }
        } catch (Exception $e) {
            Mage::helper('bronto_common')->writeError($e);
        }

        return false;
    }
}
