<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Verify_Model_System_Config_Backend_Magecron extends Mage_Core_Model_Config_Data
{

    /**
     * Processing object before save data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        $path     = $this->getPath();
        $realPath = str_replace('-', '/', array_pop(explode('/', $path)));

        // Save "Real" Config Value
        $this->_saveConfigData($realPath, $this->getValue());

        // If disabling using Magento Cron, delete from config
        if ($this->getValue() == '0') {
            $module     = array_shift(explode('/', $realPath));
            $module     = $module == 'bronto_api' ? 'bronto_common/api' : $module;
            $stringPath = Mage::helper($module)->getCronStringPath();
            $modelPath  = Mage::helper($module)->getCronModelPath();

            // Delete Cron String Config Entry
            $this->_deleteConfigData($stringPath);

            // Delete Cron Model Config Entry
            $this->_deleteConfigData($modelPath);
        }

        parent::_beforeSave();
    }

    /**
     * @param string $path
     * @param string $value
     *
     * @return Bronto_Verify_Model_System_Config_Backend_Magecron
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

    /**
     * Delete Config Value by Path
     *
     * @param string $path
     *
     * @return $this
     */
    protected function _deleteConfigData($path)
    {
        Mage::getModel('core/config_data')
            ->load($path, 'path')
            ->delete();

        return $this;
    }
}
