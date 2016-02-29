<?php
// @codingStandardsIgnoreStart
/**
 * StoreFront Consulting Kount Magento Extension
 *
 * PHP version 5
 *
 * @category  SFC
 * @package   SFC_Kount
 * @copyright 2009-2015 StoreFront Consulting, Inc. All Rights Reserved.
 *
 */
// @codingStandardsIgnoreEnd

class SFC_Kount_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Log file
     */
    const KOUNT_LOG_FILE = 'kount.log';

    /**
     * Data collector dimensions
     */
    const RIS_DATACOLLECTOR_WIDTH = 1;
    const RIS_DATACOLLECTOR_HEIGHT = 1;

    /**
     * Return the version number of the installed extension
     */
    public function getExtensionVersion()
    {
        return (string)Mage::getConfig()->getNode()->modules->SFC_Kount->version;
    }

    /**
     * Check users Ip address against settings
     * @return boolean
     */
    public function checkIPAddress($ipAddress)
    {
        // Enabled?
        if (!Mage::getStoreConfig('kount/phonetoweb/enable')) {
            return false;
        }

        // Ips, what we got?
        $aIps = explode("\n", str_replace("\r", '', Mage::getStoreConfig('kount/phonetoweb/ipaddresses')));
        $sIp = $ipAddress;
        if (strlen($sIp) > 15) {
            Mage::log("IPv6 Address {$sIp} found, bypassing Data Collector.", Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            return true;
        }
        if (in_array($sIp, $aIps)) {
            Mage::log("IP Address {$sIp} in white-listed, bypassing Data Collector.", Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            return true;
        }

        return false;
    }

    public function shouldSkipAdminProcessing()
    {
        // Check if current store is admin panel and if Kount is disabled for admin orders
        if (Mage::app()->getStore()->isAdmin() && Mage::getStoreConfig('kount/admin/enable') != '1') {
            Mage::log('Kount disabled for Admin panel order.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            return true;
        }
        else {
            // Other wise return false
            return false;
        }
    }

    /**
     * Get ris server
     * @return string
     */
    public function getRisServer()
    {
        if(Mage::getStoreConfig('kount/account/test'))
            return 'https://risk.test.kount.net';
        else 
            return 'https://risk.kount.net';
    }

    /**
     * Get data collector server
     * @return string
     */
    public function getDataCollectorServer()
    {
        if(Mage::getStoreConfig('kount/account/test'))
            return 'https://tst.kaptcha.com';
        else
            return 'https://ssl.kaptcha.com';
    }

    /**
     * Validate configuration
     * @return boolean
     */
    public function validateConfig()
    {
        // Log
        Mage::log('Checking for valid config.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Log configuration info
        Mage::log('==== Extension Configuration ====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('=================================', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('Configured Merchant Num: ' . Mage::getStoreConfig('kount/account/merchantnum'), Zend_Log::INFO,
            SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('Configured website: ' . Mage::getStoreConfig('kount/account/website'), Zend_Log::INFO,
            SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('Configured Test Mode: ' . Mage::getStoreConfig('kount/account/test'), Zend_Log::INFO,
            SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            
        if(Mage::getStoreConfig('kount/account/test')) {
            Mage::log('Configured ris: https://risk.test.kount.net', Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            Mage::log('Configured datacollector: https://tst.kaptcha.com', Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            Mage::log('Configured awc: https://awc.test.kount.net', Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        } else {
            Mage::log('Configured ris: https://risk.kount.net', Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            Mage::log('Configured datacollector: https://ssl.kaptcha.com', Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
            Mage::log('Configured awc: https://awc.kount.net', Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        }
            
        Mage::log('Payment Review Workflow: ' . Mage::getStoreConfig('kount/workflow/workflow_mode'), Zend_Log::INFO,
            SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('Notiy Kount on Decline: ' . Mage::getStoreConfig('kount/workflow/notify_processor_decline'), Zend_Log::INFO,
            SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('=================================', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Validate multi-byte string function
        if (!function_exists('mb_strpos')) {

            Mage::log(
                'Multibyte string is not installed for your PHP version. Kount\'s Sdk and Api require this to run this extension.',
                Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            return false;
        }


        // Lets get required values
        $aValues = array();
        $aValues[] = Mage::getStoreConfig('kount/account/merchantnum');
        $aValues[] = Mage::getStoreConfig('kount/account/website');

        // Validate
        foreach ($aValues as $sValue) {
            if (!$sValue || strlen(trim($sValue)) == 0) {

                // Log
                Mage::log("Failed for config value: {$sValue}", Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

                return false;
            }
        }

        return true;
    }

}
