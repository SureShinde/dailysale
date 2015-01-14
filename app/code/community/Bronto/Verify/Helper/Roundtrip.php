<?php

/**
 * API Roundtrip Validation Helper
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Helper_Roundtrip
    extends Bronto_Verify_Helper_Data
{
    /**
     * API Token Status
     *
     * @var integer
     * @access private
     */
    private $_status = 0;

    /**
     * Get API Token Validation Status
     */
    public function __construct()
    {
        $this->_status = Mage::getStoreConfig($this->getPath('roundtrip_status'));
    }

    /**
     * Get the overall status of the API Token verification
     *
     * @return string
     * @access public
     */
    public function getRoundtripStatus()
    {
        return $this->_status;
    }

    /**
     * Get a formatted version of the API Token status text
     *
     * @return string
     * @access public
     */
    protected function _getRoundtripStatusText()
    {
        switch ($this->_status) {
            case 1:
                return '<span id="bronto-roundtrip-status" class="valid">Passed Verification</span>';
                break;
            case 0:
                return '<span id="bronto-roundtrip-status" class="invalid">Failed Verification</span>';
                break;
            default:
                return '<span id="bronto-roundtrip-status" class="">Needs Verification</span>';
                break;
        }
    }

    /**
     * Get a formatted version of the API Token status text scoped to current admin scope
     *
     * @return string
     */
    public function getAdminScopedRoundtripStatusText()
    {
        $this->_status = $this->getAdminScopedConfig($this->getPath('roundtrip_status'));

        return $this->_getRoundtripStatusText();
    }
}
