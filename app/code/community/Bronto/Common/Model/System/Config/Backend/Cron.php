<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
abstract class Bronto_Common_Model_System_Config_Backend_Cron
    extends Mage_Core_Model_Config_Data
{
    /**
     * @var string
     */
    protected $_cron_string_path;

    /**
     * @var string
     */
    protected $_cron_model_path;

    /**
     * @var string
     */
    protected $_xml_path_enabled = 'enabled';

    /**
     * @var string
     */
    protected $_xml_path_mage_cron = 'mage_cron';

    /**
     * @return string
     */
    public function getCronStringPath()
    {
        return $this->_cron_string_path;
    }

    /**
     * @return string
     */
    public function getCronModelPath()
    {
        return $this->_cron_model_path;
    }

    /**
     * Cron settings after save
     *
     * @return Bronto_Common_Model_System_Config_Backend_Cron
     */
    protected function _afterSave()
    {
        $cronExprString = '';

        $useMageCron = $this->getFieldsetOrInheritedValue($this->_xml_path_mage_cron); //bronto_verify/cron_settings/

        $pathParts  = explode('/', $this->getPath());
        $pathValues = array_values($pathParts);
        $pathPart   = array_pop($pathValues);
        if ($pathPart == 'mage_cron') {
            $verify_path = 'bronto_verify/cron_settings/' . implode('-', $pathParts);
            $this->_saveConfigData($verify_path, $useMageCron);
        }

        if ($this->getFieldsetDataValue($this->_xml_path_enabled) && '1' == $useMageCron) {
            $minutely  = Bronto_Common_Model_System_Config_Source_Cron_Frequency::CRON_MINUTELY;
            $hourly    = Bronto_Common_Model_System_Config_Source_Cron_Frequency::CRON_HOURLY;
            $daily     = Bronto_Common_Model_System_Config_Source_Cron_Frequency::CRON_DAILY;
            $frequency = $this->getFieldsetOrInheritedValue('frequency');

            if ($frequency == $minutely) {
                $interval       = (int)$this->getFieldsetOrInheritedValue('interval');
                $cronExprString = "*/{$interval} * * * *";
            } elseif ($frequency == $hourly) {
                $minutes = (int)$this->getFieldsetOrInheritedValue('minutes');
                if ($minutes >= 0 && $minutes <= 59) {
                    $cronExprString = "{$minutes} * * * *";
                } else {
                    Mage::throwException(Mage::helper('bronto_common')->__('Please, specify correct minutes of hour.'));
                }
            } elseif ($frequency == $daily) {
                $time        = $this->getFieldsetOrInheritedValue('time');
                $timeMinutes = $time[1];
                $timeHours   = $time[0];
                // Fix Midnight Issue
                if ('00' == $timeMinutes && '00' == $timeHours) {
                    $timeMinutes = '59';
                    $timeHours   = '23';
                }
                $cronExprString = "{$timeMinutes} {$timeHours} * * *";
            }
        }

        try {
            if ($this->getCronStringPath()) {
                if ('0' == $useMageCron) {
                    $this->_deleteConfigData($this->getCronStringPath());
                } else {
                    $this->_saveConfigData($this->getCronStringPath(), $cronExprString);
                }
            }
            if ($this->getCronModelPath()) {
                if ('0' == $useMageCron) {
                    $this->_deleteConfigData($this->getCronModelPath());
                } else {
                    $this->_saveConfigData(
                        $this->getCronModelPath(),
                        (string)Mage::getConfig()->getNode($this->getCronModelPath())
                    );
                }
            }
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('adminhtml')->__('Unable to save Cron expression'));
        }
    }

    /**
     * Gets the fieldsetform key or an inherited value
     *
     * @param string key
     * @return mixed
     */
    public function getFieldsetOrInheritedValue($key)
    {
        $fieldSetValue = $this->getFieldsetDataValue($key);
        if (empty($fieldSetValue)) {
            $helper = Mage::helper('bronto_common');
            $path = preg_replace('|/[^/]+$|', '/' . $key, $this->getPath());
            return $helper->getAdminScopedConfig($path, 'default');
        }
        return $fieldSetValue;
    }

    /**
     * Get value by key for new user data from <section>/groups/<group>/fields/<field>
     *
     * @param string $key
     *
     * @return string
     */
    public function getFieldsetDataValue($key)
    {
        if (method_exists('Mage_Core_Model_Config_Data', 'getFieldsetDataValue')) {
            return parent::getFieldsetDataValue($key);
        }

        // Handle older Magento versions
        $data = $this->_getData('fieldset_data');
        if (is_array($data) && isset($data[$key])) {
            return $data[$key];
        }

        $data    = $this->getData();
        $groups  = isset($data['groups']) ? $data['groups'] : array();
        $groupId = isset($data['group_id']) ? $data['group_id'] : array();
        foreach ($groups as $group => $fields) {
            $fields = isset($fields['fields']) ? $fields['fields'] : $fields;
            if ($group == $groupId) {
                if (isset($fields[$key]['value'])) {
                    return $fields[$key]['value'];
                }
            }
        }

        return null;
    }

    /**
     * Save Config Value by Path
     *
     * @param string $path
     * @param mixed  $value
     *
     * @return Bronto_Common_Model_System_Config_Backend_Cron
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
     * @return Bronto_Common_Model_System_Config_Backend_Cron
     */
    protected function _deleteConfigData($path)
    {
        Mage::getModel('core/config_data')
            ->load($path, 'path')
            ->delete();

        return $this;
    }
}
