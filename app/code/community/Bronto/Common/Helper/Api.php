<?php

class Bronto_Common_Helper_Api extends Bronto_Common_Helper_Data
{
    const XML_PATH_ENABLED       = 'bronto_api/settings/enabled';
    const XML_PATH_MAGE_CRON     = 'bronto_api/settings/mage_cron';
    const XML_PATH_ATTEMPT_THRES = 'bronto_api/settings/threshold';
    const XML_PATH_TIME          = 'bronto_api/settings/time';
    const XML_PATH_INTERVAL      = 'bronto_api/settings/interval';
    const XML_PATH_FREQUENCY     = 'bronto_api/settings/frequency';
    const XML_PATH_FREQUENCY_MIN = 'bronto_api/settings/minutes';
    const XML_PATH_ERROR_THRES   = 'bronto_api/setting/limit';


    const XML_PATH_QUEUE_ENABLED       = 'bronto_api/queue_settings/enabled';
    const XML_PATH_QUEUE_MAGE_CRON     = 'bronto_api/queue_settings/mage_cron';
    const XML_PATH_QUEUE_LIMIT         = 'bronto_api/queue_settings/limit';
    const XML_PATH_QUEUE_TIME          = 'bronto_api/queue_settings/time';
    const XML_PATH_QUEUE_INTERVAL      = 'bronto_api/queue_settings/interval';
    const XML_PATH_QUEUE_FREQUENCY     = 'bronto_api/queue_settings/frequency';
    const XML_PATH_QUEUE_FREQUENCY_MIN = 'bronto_api/queue_settings/minutes';

    const XML_PATH_SOAP_CLIENT             = 'bronto_api/soap_options/soap_client';
    const XML_PATH_API_OBSERVER            = 'bronto_api/soap_options/observer';
    const XML_PATH_API_RETRYER             = 'bronto_api/soap_options/retryer';
    const XML_PATH_SOAP_STREAM_CONTEXT     = 'bronto_api/soap_options/stream_context';
    const XML_PATH_SOAP_RETRY_LIMIT        = 'bronto_api/soap_options/retry_limit';
    const XML_PATH_SOAP_CONNECTION_TIMEOUT = 'bronto_api/soap_options/connection_timeout';
    const XML_PATH_SOAP_TRACE              = 'bronto_api/soap_options/trace';
    const XML_PATH_SOAP_EXCEPTIONS         = 'bronto_api/soap_options/exceptions';
    const XML_PATH_WSDL_CACHE              = 'bronto_api/soap_options/wsdl_cache';

    const DEFAULT_SOAP_CLIENT              = 'Bronto_SoapClient';

    /**
     * Gets the Canonical name of the helper
     *
     * @return string
     */
    public function getName()
    {
        return $this->__('Bronto API Retry');
    }

    /**
     * Gets the Canonical name of the Send Queue
     *
     * @return string
     */
    public function getSendName()
    {
        return $this->__('Bronto Send Queue');
    }

    /**
     * Checks if the api retryer is enabled
     *
     * @return bool
     */
    public function isEnabled($scope = 'default', $scopeId = 0)
    {
        return (bool) $this->getAdminScopedConfig(self::XML_PATH_ENABLED);
    }

    /**
     * @see parent
     */
    public function disableModule($scope = 'default', $scopeId = 0, $deleteConfig = false)
    {
        return (
            $this->_disableModule(self::XML_PATH_ENABLED, $scope, $scopeId, $deleteConfig) &&
            $this->_disableModule(self::XML_PATH_QUEUE_ENABLED, $scope, $scopeId, $deleteConfig)
        );
    }

    /**
     * Checked if the send queue is enabled
     *
     * @param string $scope
     * @param int $scopeId
     * @return bool
     */
    public function canUseQueue($scope = 'default', $scopeId = 0)
    {
        return (bool) $this->getAdminScopedConfig(self::XML_PATH_QUEUE_ENABLED, $scope, $scopeId);
    }

    /**
     * Check if the cron is magento
     *
     * @param string $scope
     * @param int $scopeId
     * @return bool
     */
    public function queueCanUseMageCron($scope = 'default', $scopeId = 0)
    {
        return (bool) $this->getAdminScopedConfig(self::XML_PATH_QUEUE_MAGE_CRON, $scope, $scopeId);
    }

    /**
     * Gets the send queue threshold
     *
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getSendLimit($scope = 'default', $scopeId = 0)
    {
        return (int) $this->getAdminScopedConfig(self::XML_PATH_QUEUE_LIMIT, $scope, $scopeId);
    }

    /**
     * Gets the entries whose attempts are less than this amount
     *
     * @return int
     */
    public function getAttemptThreshold()
    {
        return (int) $this->getAdminScopedConfig(self::XML_PATH_ATTEMPT_THRES);
    }

    /**
     * Gets the number of error entries to process
     *
     * @return int
     */
    public function getErrorThreshold()
    {
        return (int) $this->getAdminScopedConfig(self::XML_PATH_ERROR_THRES);
    }

    /**
     * Whether or not to use Magento cron
     *
     * @return bool
     */
    public function canUseMageCron()
    {
        return (bool) $this->getAdminScopedConfig(self::XML_PATH_MAGE_CRON, 'default', 0);
    }

    /**
     * Get SOAP Options
     *
     * @return array
     */
    public function getSoapOptions()
    {
        // Return Default Options
        return array(
            'soap_client'        => $this->getSoapClient(),
            'observer'           => $this->getApiObserver(),
            'retry_limit'        => $this->getSoapRetryLimit(),
            'connection_timeout' => $this->getSoapConnectionTimeout(),
            'trace'              => $this->getSoapTrace(),
            'exceptions'         => $this->getSoapExceptions(),
            'cache_wsdl'         => $this->getSoapCacheWsdl(),
            'debug'              => $this->isDebugEnabled(),
            'retryer'            => $this->getApiRetryer()
        );
    }

    /**
     * Gets the class name for the retryer
     *
     * @return string
     */
    public function getApiRetryer()
    {
        $class = $this->getAdminScopedConfig(self::XML_PATH_API_RETRYER);
        $options = array('type' => 'custom');
        if (empty($class) || !class_exists($class)) {
            $options['object'] = Mage::getModel('bronto_common/error');
        } else {
            $options['path'] = $class;
        }
        return $options;
    }

    /**
     * Override the Bronto_SoapCLient class name
     *
     * @return string
     */
    public function getSoapClient()
    {
        $class = $this->getAdminScopedConfig(self::XML_PATH_SOAP_CLIENT);
        if (empty($class)) {
            $class = self::DEFAULT_SOAP_CLIENT;
        }
        if (
            $this->isStreamContextOverride() &&
            $class == self::DEFAULT_SOAP_CLIENT
        ) {
            $class = 'Bronto_Common_Model_SoapClient';
        }
        return $class;
    }

    /**
     * Overrides the Bronto_Observer use in Bronto_Api
     *
     * @return string
     */
    public function getApiObserver()
    {
        return $this->getAdminScopedConfig(self::XML_PATH_API_OBSERVER);
    }

    /**
     * Override the default Soap client with the stream context override
     *
     * @return bool
     */
    public function isStreamContextOverride()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_SOAP_STREAM_CONTEXT);
    }

    /**
     * Turn on stream context override
     *
     * @param bool $state
     * @return Bronto_Common_Helper_Api
     */
    public function setStreamContext($state)
    {
        $config = Mage::getModel('core/config');
        $config->saveConfig(self::XML_PATH_SOAP_STREAM_CONTEXT, $state ? '1' : '0', 'default', 0);

        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();
        return $this;
    }

    /**
     * Get Config Value for SOAP Retry Limit
     *
     * @return int
     */
    public function getSoapRetryLimit()
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_SOAP_RETRY_LIMIT);
    }

    /**
     * Get Config Value for SOAP Connection Timeout
     *
     * @return int
     */
    public function getSoapConnectionTimeout()
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_SOAP_CONNECTION_TIMEOUT);
    }

    /**
     * Get Config Value for SOAP Trace
     *
     * @return bool
     */
    public function getSoapTrace()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_SOAP_TRACE) == '1';
    }

    /**
     * Get Config Value for SOAP Exceptions
     *
     * @return bool
     */
    public function getSoapExceptions()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_SOAP_EXCEPTIONS) == '1';
    }

    /**
     * @return string
     */
    public function getSoapCacheWsdl()
    {
        $cacheWsdl = $this->getAdminScopedConfig(self::XML_PATH_WSDL_CACHE);
        switch ($cacheWsdl) {
            case 'WSDL_CACHE_NONE':
                return WSDL_CACHE_NONE;
            case 'WSDL_CACHE_DISK':
                return WSDL_CACHE_DISK;
            case 'WSDL_CACHE_MEMORY':
                return WSDL_CACHE_MEMORY;
            case 'WSDL_CACHE_BOTH':
            default:
                return WSDL_CACHE_BOTH;
        }
    }

}
