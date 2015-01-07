<?php

/**
 * @package   Newsletter
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Newsletter_Helper_Data extends Bronto_Common_Helper_Data
{
    const XML_PATH_ENABLED         = 'bronto_newsletter/settings/enabled';
    const XML_PATH_MAGE_CRON       = 'bronto_newsletter/settings/mage_cron';
    const XML_PATH_LIMIT           = 'bronto_newsletter/settings/limit';
    const XML_PATH_SYNC_LIMIT      = 'bronto_newsletter/settings/sync_limit';
    const XML_PATH_DEFAULT         = 'bronto_newsletter/checkout/default_checked';
    const XML_PATH_SHOW_LOGGEDIN   = 'bronto_newsletter/checkout/show_to_loggedin';
    const XML_PATH_SHOW_GUEST      = 'bronto_newsletter/checkout/show_to_guests';
    const XML_PATH_SHOW_REGISTRAR  = 'bronto_newsletter/checkout/show_to_registrars';
    const XML_PATH_SHOW_SUBSCRIBED = 'bronto_newsletter/checkout/show_if_subscribed';
    const XML_PATH_LABEL_TEXT      = 'bronto_newsletter/checkout/label_text';
    const XML_PATH_CHECKBOX_CSS    = 'bronto_newsletter/checkout/css_selector';
    const XML_PATH_CHECKBOX_STYLE  = 'bronto_newsletter/checkout/checkbox_style';
    const XML_PATH_INSTALL_DATE    = 'bronto_newsletter/settings/install_date';
    const XML_PATH_UPGRADE_DATE    = 'bronto_newsletter/settings/upgrade_date';

    const XML_PATH_CRON_STRING = 'crontab/jobs/bronto_newsletter_import/schedule/cron_expr';
    const XML_PATH_CRON_MODEL  = 'crontab/jobs/bronto_newsletter_import/run/model';

    /**
     * Module Human Readable Name
     */
    protected $_name = 'Bronto Newsletter Opt-In';

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
     * Check if module is enabled
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return bool
     */
    public function isEnabled($scope = 'default', $scopeId = 0)
    {
        // Get Enabled Scope
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_ENABLED, $scope, $scopeId);
    }

    /*
     * Get Text to display in notice when enabling module
     *
     * @return string
     */
    public function getModuleEnabledText()
    {
        $message   = parent::getModuleEnabledText();
        $scopeData = $this->getScopeParams();
        if ($scopeData['scope'] != 'default') {
            $message = $this->__(
                'If the API token being used for this configuration scope is different from that of the Default Config scope, ' .
                'you should un-check the `Use Website` or `Use Default` for the <em>Add to List(s)</em> field in the <em>Contacts</em> ' .
                'group on this page and select the desired list(s).'
            );
        }

        return $message;
    }

    /**
     * Disable Specified Module
     *
     * @param string $scope
     * @param int    $scopeId
     * @param bool   $deleteConfig
     *
     * @return bool
     */
    public function disableModule($scope = 'default', $scopeId = 0, $deleteConfig = false)
    {
        return $this->_disableModule(self::XML_PATH_ENABLED, $scope, $scopeId, $deleteConfig);
    }

    /**
     * Gets the CSS selector for the checkbox
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getCssSelector($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_CHECKBOX_CSS, $scope, $scopeId);
    }

    /**
     * Gets the Checkbox styles
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getCheckboxStyle($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_CHECKBOX_STYLE, $scope, $scopeId);
    }

    /**
     * @return int
     */
    public function getSyncLimit()
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_SYNC_LIMIT);
    }

    /**
     * Get import limit from config
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return int
     */
    public function getLimit($scope = 'default', $scopeId = 0)
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_LIMIT, $scope, $scopeId);
    }

    /**
     * Check if module can use the magento cron
     *
     * @return bool
     */
    public function canUseMageCron()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_MAGE_CRON, 'default', 0);
    }

    /**
     * @return string
     */
    public function getCronStringPath()
    {
        return self::XML_PATH_CRON_STRING;
    }

    /**
     * @return string
     */
    public function getCronModelPath()
    {
        return self::XML_PATH_CRON_MODEL;
    }

    /**
     * @return bool
     */
    public function isEnabledCheckedByDefault()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_DEFAULT);
    }

    /**
     * @return bool
     */
    public function isEnabledForLoggedinCheckout()
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_SHOW_LOGGEDIN);
    }

    /**
     * @return bool
     */
    public function isEnabledForGuestCheckout()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_SHOW_GUEST);
    }

    /**
     * @return bool
     */
    public function isEnabledForRegisterCheckout()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_SHOW_REGISTRAR);
    }

    /**
     * @return bool
     */
    public function isEnabledIfAlreadySubscribed()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_SHOW_SUBSCRIBED);
    }

    /**
     * @return string
     */
    public function getCheckboxLabelText()
    {
        return $this->getAdminScopedConfig(self::XML_PATH_LABEL_TEXT);
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return boolean
     */
    public function isCustomerSubscribed(Mage_Customer_Model_Customer $customer = null)
    {
        if (!$customer) {
            return false;
        }

        /* @var $subscriber Mage_Newsletter_Model_Subscriber */
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);

        return (bool)$subscriber->isSubscribed();
    }

    /**
     * Retrieve helper module name
     *
     * @return string
     */
    protected function _getModuleName()
    {
        return 'Bronto_Newsletter';
    }

    /**
     * Get Count of Subscribers not in queue
     *
     * @return int
     */
    public function getMissingSubscribersCount()
    {
        return Mage::getModel('bronto_newsletter/queue')
            ->getMissingSubscribersCount();
    }

    /**
     * Get Subscribers which aren't in queue
     *
     * @return array
     */
    public function getMissingSubscribers()
    {
        return Mage::getModel('bronto_newsletter/queue')
            ->getMissingSubscribers();
    }
}
