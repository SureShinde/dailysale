<?php

/**
 * Class SFC_Kount_Model_ConfigFileReader
 *
 * This class plugs in to the Kount PHP SDK to pull configuration from Magento DB instead of the .ini file in file system.
 */
class SFC_Kount_Model_ConfigFileReader
{

    /**
     * Private constructor to prevent direct object instantiation.
     */
    public function __construct()
    {
    }

    /**
     * Get static RIS settings from Magento config
     * @return array settings Hash map
     */
    public function getSettings()
    {
        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');

        // Build array of settings
        $settings = array();
        $settings['MERCHANT_ID'] = Mage::getStoreConfig('kount/account/merchantnum');
        if(Mage::getStoreConfig('kount/account/test'))
            $settings['URL'] = 'https://risk.test.kount.net';
        else
            $settings['URL'] = 'https://risk.kount.net';
        // Logging config
        if (Mage::getStoreConfig('dev/log/active') == '1' && Mage::getStoreConfig('kount/log/enable') == '1') {
            $settings['LOGGER'] = 'SIMPLE';
            $settings['SIMPLE_LOG_LEVEL'] = 'DEBUG';
            $settings['SIMPLE_LOG_FILE'] = SFC_Kount_Helper_Data::KOUNT_LOG_FILE;
            $settings['SIMPLE_LOG_PATH'] = Mage::getBaseDir('log');
        }
        else {
            $settings['LOGGER'] = 'NOP';
            $settings['SIMPLE_LOG_LEVEL'] = 'FATAL';
            $settings['SIMPLE_LOG_FILE'] = null;
            $settings['SIMPLE_LOG_PATH'] = null;
        }
        $settings['PEM_CERTIFICATE'] = null;
        $settings['PEM_KEY_FILE'] = null;
        $settings['PEM_PASS_PHRASE'] = null;
        $settings['API_KEY'] = Mage::getStoreConfig('kount/account/api_key');
        $settings['CONNECT_TIMEOUT'] = 20;


        // Return the settings
        return $settings;
    }

    /**
     * Get a named configuration setting.
     * @param string $name Get a named configuration file setting
     * @return string
     * @throws Exception If the specified setting name does not exist.
     */
    public function getConfigSetting($name)
    {
        $settings = $this->getSettings();
        if (array_key_exists($name, $settings)) {
            return $settings[$name];
        }
        throw new Exception("The configuration setting [{$name}] is not defined");
    }

}
