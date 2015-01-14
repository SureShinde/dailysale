<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Email_Model_System_Config_Backend_Templates_Field extends Mage_Core_Model_Config_Data
{

    /**
     * Processing object before save data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        $path     = $this->getPath();
        $realpath = str_replace('-', '/', array_pop(explode('/', $path)));
        $this->_saveConfigData($realpath, $this->getValue());

        parent::_beforeSave();
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
        Mage::getModel('core/config_data')
            ->load($path, 'path')
            ->setValue($value)
            ->setPath($path)
            ->save();

        return $this;
    }

}
