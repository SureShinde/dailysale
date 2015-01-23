<?php

/**
 * Verify Helper
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Helper_Data
    extends Bronto_Common_Helper_Data
{
    /**
     * Description for const
     */
    const XML_PATH_ROUNDTRIP_ROOT = 'bronto_verify/settings/';

    /**
     * Module Human Readable Name
     */
    protected $_name = 'Bronto Advanced Configuration';

    /**
     * Check if module is enabled (Verify Module Always Enabled)
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return bool
     */
    public function isEnabled($scope = 'default', $scopeId = 0)
    {
        true;
    }

    /**
     * Get Human Readable Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->__($this->_name);
    }

    /**
     * Get the full path from path ending
     *
     * @param string $pathend The setting to get the path for
     *
     * @return string
     * @access public
     */
    public function getPath($pathend)
    {
        return self::XML_PATH_ROUNDTRIP_ROOT . $pathend;
    }

    /**
     * Set the value of a setting
     *
     * @param string     $path The setting path to set the value for
     * @param string     $value
     * @param string     $scope
     * @param int|string $scopeId
     *
     * @return Mage_Core_Model_Config
     * @access public
     */
    public function setStatus($path, $value, $scope = null, $scopeId = null)
    {
        $scope   = (in_array($scope, array('default', 'websites', 'stores'))) ? $scope : 'default';
        $scopeId = (is_int($scopeId)) ? $scopeId : 0;

        return Mage::getSingleton('core/config')
            ->saveConfig($path, $value, $scope, $scopeId);
    }
}
