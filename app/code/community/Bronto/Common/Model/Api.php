<?php

/**
 * @category Bronto
 * @package  Common
 */
class Bronto_Common_Model_Api extends Mage_Core_Model_Abstract implements Bronto_Api_Observer
{

    protected static $_instances = array();

    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_common/api');
    }

    /**
     * Gets the Bronto_Api client for for use in Magento
     *
     * @return Bronto_Api
     */
    public function getClient()
    {
        $token = $this->getToken();
        if (!isset(self::$_instances[$token])) {
            $options = Mage::helper('bronto_common/api')->getSoapOptions();
            if (empty($options['observer'])) {
                $options['observer'] = $this;
            }
            self::$_instances[$token] = new Bronto_Api($token, $options);
        }
        if ($this->hasSessionId()) {
            self::$_instances[$token]->setSessionId($this->getSessionId());
        }
        return self::$_instances[$token];
    }

    /**
     * @see parent
     *
     * @param Bronto_Api $api
     */
    public function onBeforeLogin($api)
    {
        if ($this->hasSetSession()) {
            $this->unsSessionId();
            Mage::helper('bronto_common')->writeDebug('Session ID expired for token: ' . $this->getToken());
            $this->_setOnce = false;
        } else {
            try {
                parent::load($api->getToken());
                if ($this->hasSessionId()) {
                    $api->setSessionId($this->getSessionId());
                    Mage::helper('bronto_common')->writeDebug('Hitting API sessionId cache for token: ' . $this->getToken());
                    $this->_setOnce = true;
                }
            } catch (Exception $e) {
                // Swallow read exceptions, in case of FTP install
                Mage::helper('bronto_common')->writeError('Failed to read from api session table: ' . $e->getMessage());
            }
        }
    }

    /**
     * @see parent
     *
     * @param string $apiToken
     * @param string $sessionId
     */
    public function onLogin($apiToken, $sessionId)
    {
        try {
            $this
              ->setToken($apiToken)
              ->setSessionId($sessionId)
              ->setCreatedAt(Mage::getSingleton('core/date')->gmtDate())
              ->save();
            Mage::helper('bronto_common')->writeDebug("Initiating API for token: {$this->getToken()}");
        } catch (Exception $e) {
            Mage::helper('bronto_common')->writeError("Failed to update API {$this->getToken()} Session: " . $e->getMessage());
        }
    }

    /**
     * @see parent
     *
     * @param Bronto_Api $api
     * @param Bronto_Api_Exception $exception
     */
    public function onError(Bronto_Api $api, Bronto_Api_Exception $exception)
    {
        if ($exception instanceOf Bronto_Api_Exception) {
            if ($request = $api->getLastRequest()) {
                Mage::helper('bronto_common')->writeDebug(var_export($request, true));
            }

            if ($response = $api->getLastResponse()) {
                Mage::helper('bronto_common')->writeDebug(var_export($response, true));
            }
        }
    }
}
