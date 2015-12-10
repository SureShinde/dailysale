<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2013 Bronto Software, Inc.
 * @license   http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */
class Bronto_Common_Block_Adminhtml_System_Config_About extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Name of module
     *
     * @var string
     */
    protected $_module = 'bronto_common';

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('bronto/common/about.phtml');
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }

    /**
     * Get the module namespace
     *
     * @return string
     */
    public function getModuleNamespace()
    {
        return $this->_module;
    }

    /**
     * Get the module name
     *
     * @return string
     */
    public function getModuleName()
    {
        return Mage::helper($this->_module)->getName();
    }

    /**
     * Get Section Name
     *
     * @return string
     */
    public function getSection()
    {
        return Mage::app()->getRequest()->getParam('section');
    }

    /**
     * Get the module version
     *
     * @return string
     */
    public function getModuleVersion()
    {
        $version = Mage::helper($this->_module)->getModuleVersion();

        return empty($version) ? null : "v{$version}";
    }

    /**
     * Get log url
     *
     * @return string
     */
    public function getLogViewUrl()
    {
        return Mage::getSingleton('adminhtml/url')->getUrl('bronto/log/index', array('print' => 1, 'file' => $this->_module));
    }

    /**
     * Get if debugging is turned on for module
     *
     * @return bool
     */
    public function hasDebugEnabled()
    {
        return (bool)Mage::helper($this->_module)->isDebugEnabled();
    }

    /**
     * Get if the log file exists
     *
     * @return bool
     */
    public function logFileExists()
    {
        $logFile = Mage::getBaseDir('log') . DIRECTORY_SEPARATOR . "{$this->_module}.log";

        return (bool)@file_exists($logFile);
    }

    /**
     * Forwards call to helper from view
     *
     * @return boolean
     */
    public function isValidApiToken()
    {
        return Mage::helper('bronto_verify/apitoken')->getStatus();
    }
}
