<?php

/**
 * API Token Validation Helper
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 */
class Bronto_Verify_Helper_Apitoken
    extends Bronto_Verify_Helper_Data
{
    /**
     * API Token Status
     *
     * @var integer
     * @access private
     */
    private $_status = false;

    /**
     * API Token
     *
     * @var string
     * @access private
     */
    private $_token = '';

    /**
     * Token Failure Reason Message
     *
     * @var string
     * @access private
     */
    private $_reason = '';

    /**
     * Get API Token Validation Status
     *
     * @return Bronto_Verify_Helper_Apitoken
     *
     * @access public
     */
    public function __construct()
    {
        $this->_status = Mage::helper('bronto_common')->getAdminScopedConfig($this->getPath('token_status'));
        $this->_token  = Mage::helper('bronto_common')->getApiToken();

        return $this;
    }

    /**
     * Set API Token
     *
     * @param string $token
     *
     * @return $this
     */
    public function setApiToken($token)
    {
        $this->_token = $token;

        return $this;
    }

    /**
     * Set API Token Status
     *
     * @param int $status
     *
     * @return Bronto_Verify_Helper_Apitoken
     */
    public function setApitokenStatus($status)
    {
        $this->_status = $status;

        return $this;
    }

    /**
     * Get the overall status of the API Token verification
     *
     * @return string
     * @access public
     */
    public function getApitokenStatus()
    {
        // Check Permissions First
        $this->_checkApitokenPermissions();

        return $this->_status;
    }

    /**
     * Get Token Status reason message
     *
     * @return string
     * @access public
     */
    public function getReason()
    {
        return $this->_reason;
    }

    /**
     * Determines if this the API status is in a success state
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->_status == 1;
    }

    /**
     * Get Permissions Text
     *
     * @return string
     * @access protected
     */
    protected function _checkApitokenPermissions()
    {
        try {
            $api      = $this->getApi($this->_token);
            $tokenRow = $api->getTokenInfo();

            $access = $tokenRow->hasPermissions(7);

            if (!$access) {
                $this->_status = 0;
                $this->_reason = 'Token Does Not Have Full Access';
            }
        } catch (Exception $e) {
            if (102 == $e->getCode()) {
                $this->_status = 0;
                $this->_reason = 'Token is Not Active or is Invalid';
            } else {
                $this->writeError($e);
            }
        }

        return $this->_reason;
    }

    /**
     * Get a formatted version of the API Token status text
     *
     * @return string
     * @access public
     */
    protected function _getApitokenStatusText()
    {
        $permissionText = $this->_checkApitokenPermissions();
        if ('' != $permissionText) {
            $permissionText = '<br /><span style="color: #2f2f2f;">Reason: </span>' . $permissionText;
        }

        switch ($this->_status) {
            case 1:
                return '<span id="bronto-validation-status-text" class="valid">Passed Verification</span>' . $permissionText;
                break;
            case 0:
                return '<span id="bronto-validation-status-text" class="invalid">Failed Verification</span><br />' . $permissionText;
                break;
            default:
                return '<span id="bronto-validation-status-text" class="">Needs Verification</span><br />' . $permissionText;
                break;
        }
    }

    /**
     * Get a formatted version of the API Token status text scoped to current admin scope
     *
     * @return string
     */
    public function getAdminScopedApitokenStatusText()
    {
        if (false === $this->_status) {
            $this->_status = $this->getAdminScopedConfig($this->getPath('token_status'));
        }

        return $this->_getApitokenStatusText();
    }
}
