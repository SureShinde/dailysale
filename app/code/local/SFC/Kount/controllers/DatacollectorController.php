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

class SFC_Kount_DatacollectorController extends Mage_Core_Controller_Front_Action
{
    /**
     * Index action
     */
    public function indexAction()
    {
        // Nothing to do
    }

    /**
     * Iframe action
     */
    public function iframeAction()
    {
        // Increment Kount Session ID
        $session = Mage::getSingleton('kount/session');
        $session->incrementKountSessionId();
        
        // Log
        Mage::log('Data collector iframe action', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Enabled?
        if (!Mage::getStoreConfig('kount/account/enabled')) {
            Mage::log('Kount extension is disabled in system configuration, skipping action', Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            return;
        }

        // Validate Kount settings
        /** @var SFC_Kount_Helper_Data $helper */
        $helper = Mage::helper('kount');
        if (!$helper->validateConfig()) {
            Mage::log('Kount settings not configured, skipping action', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            return;
        }

        // Redirect
        if (!$this->_checkIPAddress()) {
            Mage::app()->getResponse()->setRedirect($this->_getUrl('htm'));
            Mage::app()->getResponse()->sendResponse();
        }
    }

    /**
     * Gif action
     */
    public function gifAction()
    {
        // Log
        Mage::log('Data collector gif action', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Enabled?
        if (!Mage::getStoreConfig('kount/account/enabled')) {
            Mage::log('Kount extension is disabled in system configuration, skipping action', Zend_Log::INFO,
                SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            return;
        }

        // Validate Kount settings
        /** @var SFC_Kount_Helper_Data $helper */
        $helper = Mage::helper('kount');
        if (!$helper->validateConfig()) {
            Mage::log('Kount settings not configured, skipping action', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            return;
        }

        // Redirect
        if (!$this->_checkIPAddress()) {
            Mage::app()->getResponse()->setRedirect($this->_getUrl('gif'));
            Mage::app()->getResponse()->sendResponse();
        }
    }

    /**
     * Get url
     * @param string Type
     * @return string
     */
    private function _getUrl($sMode)
    {
        // Helper
        /** @var SFC_Kount_Helper_Data $helper */
        $helper = Mage::helper('kount');

        // Url for logo
        return sprintf(
            '%s/logo.%s?m=%s&s=%s',
            $helper->getDataCollectorServer(),
            $sMode,
            Mage::getStoreConfig('kount/account/merchantnum'),
            Mage::getSingleton('kount/session')->getKountSessionId());
    }

    /**
     * Check users Ip address against settings
     * @return boolean
     */
    private function _checkIPAddress()
    {
        return Mage::helper('kount')->checkIPAddress(Mage::helper('core/http')->getRemoteAddr());
    }

}