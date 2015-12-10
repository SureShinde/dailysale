<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Helper_Data extends Bronto_Common_Helper_Data implements Bronto_Common_Helper_DataInterface
{
    const XML_PATH_ENABLED            = 'bronto_reminder/settings/enabled';
    const XML_PATH_MAGE_CRON          = 'bronto_reminder/settings/mage_cron';
    const XML_PATH_LOG_ENABLED        = 'bronto_reminder/settings/log_enabled';
    const XML_PATH_LOG_FIELDS_ENABLED = 'bronto_reminder/settings/log_fields_enabled';
    const XML_PATH_ALLOW_SEND         = 'bronto_reminder/settings/allow_send';
    const XML_PATH_TIME               = 'bronto_reminder/settings/time';
    const XML_PATH_INTERVAL           = 'bronto_reminder/settings/interval';
    const XML_PATH_FREQUENCY          = 'bronto_reminder/settings/frequency';
    const XML_PATH_FREQUENCY_MIN      = 'bronto_reminder/settings/minutes';
    const XML_PATH_SEND_LIMIT         = 'bronto_reminder/settings/limit';
    const XML_PATH_EMAIL_IDENTITY     = 'bronto_reminder/settings/identity';
    const XML_PATH_EMAIL_THRESHOLD    = 'bronto_reminder/settings/threshold';
    const XML_PATH_EXCLUSION_LISTS    = 'bronto_reminder/settings/exclusion';

    const XML_PATH_CRON_STRING = 'crontab/jobs/bronto_reminder_send_notification/schedule/cron_expr';
    const XML_PATH_CRON_MODEL  = 'crontab/jobs/bronto_reminder_send_notification/run/model';

    /**
     * Module Human Readable Name
     */
    protected $_name = 'Bronto Reminder Emails';

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
     * Retrieve helper module name
     *
     * @return string
     */
    protected function _getModuleName()
    {
        return 'Bronto_Reminder';
    }

    /**
     * Get link to transactional email configuration
     *
     * @return string
     */
    public function getConfigLink()
    {
        $url = $this->getScopeUrl('*/system_config/edit', array('section' => 'bronto_reminder'));

        return '<strong>System &rsaquo; Configuration &raquo; Bronto &rsaquo; <a href="' . $url . '" title="Reminder Emails">Reminder Emails</a></strong>';
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
            $url = Mage::helper('adminhtml')->getUrl('/reminders');

            $message = $this->__(
                'If the API token being used for this configuration scope is different from that of the Default Config scope, ' .
                'you should update any existing rules in <a href="' . $url . '">Bronto Reminder Emails</a> ' .
                'to use a message from the corresponding Bronto account.'
            );
        }

        return $message;
    }

    /**
     * Determine if any stores have module enabled
     *
     * @return bool
     */
    public function isEnabledForAny()
    {
        $stores = Mage::app()->getStores();
        if (is_array($stores) && count($stores) >= 1) {
            foreach ($stores as $store) {
                if ($this->isEnabled('store', $store->getId())) {
                    return true;
                }
            }
        }

        return false;
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
    public function isLogEnabled()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_LOG_ENABLED);
    }

    /**
     * @return bool
     */
    public function isLogFieldsEnabled()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_LOG_FIELDS_ENABLED);
    }

    /**
     * Determine if Allowed to send
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return bool
     */
    public function isAllowSend($scope = 'default', $scopeId = 0)
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_ALLOW_SEND, $scope, $scopeId);
    }

    /**
     * Determine if any stores are allowed to send
     *
     * @return bool
     */
    public function isAllowSendForAny()
    {
        $stores = Mage::app()->getStores();
        if (is_array($stores) && count($stores) >= 1) {
            foreach ($stores as $store) {
                if ($this->isAllowSend('store', $store->getId())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine if email can be sent through bronto
     *
     * @param Mage_Core_Model_Email_Template $template
     * @param null                           $storeId
     *
     * @return bool
     */
    public function canSendBronto(Mage_Core_Model_Email_Template $template, $storeId = null)
    {
        if ($this->isEnabled('store', $storeId)) {
            return true;
        }

        return false;
    }

    /**
     * Text to display when reminder module not allowed to send emails
     *
     * @return string
     */
    public function getNotAllowedText()
    {
        $url         = $this->getScopeUrl('/system_config/edit/section/bronto_reminder');
        $messageText = $this->__('Rules are currently unable to send emails.  
                You can enable this function in the System Configuration <a href="' . $url . '">Reminder Emails</a>');

        return $messageText;
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
     * @return int
     */
    public function getCronInterval()
    {
        switch ($this->getCronFrequency()) {
            case 'I':
                return (int)$this->getAdminScopedConfig(self::XML_PATH_INTERVAL);
                break;
            case 'H':
                return 60;
                break;
            case 'D':
                return 1440;
                break;
            default:
                return 5;
                break;
        }
    }

    /**
     * @return string
     */
    public function getCronFrequency()
    {
        return $this->getAdminScopedConfig(self::XML_PATH_FREQUENCY);
    }

    /**
     * @return int
     */
    public function getOneRunLimit()
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_SEND_LIMIT);
    }

    /**
     * @return string
     */
    public function getEmailIdentity()
    {
        return (string)$this->getAdminScopedConfig(self::XML_PATH_EMAIL_IDENTITY);
    }

    /**
     * @return int
     */
    public function getSendFailureThreshold()
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_EMAIL_THRESHOLD);
    }

    /**
     * @see parent
     * @return boolean
     */
    public function hasCustomConfig()
    {
        return true;
    }

    /**
     * Returns any reminder email rules with their conditions
     *
     * @return array
     */
    public function getCustomConfig($scope = 'default', $scopeId = 0)
    {
        $ruleMeta = Mage::getModel('bronto_reminder/rule');
        $rules    = $ruleMeta->getCollection()->getItems();

        $data = array();
        if (empty($rules)) {
            return $data;
        }

        $reminders = array();
        foreach ($rules as $rule) {
            $root = $rule->getConditions();

            $reminders[] = array(
                'name'       => $rule->getName(),
                'active'     => $rule->getIsActive(),
                'from'       => $rule->getFromDate(),
                'to'         => $rule->getToDate(),
                'conditions' => array(
                    'label'      => $root->asString(),
                    'conditions' => $this->_recursiveConditionLog($root),
                ),
            );
        }
        $data['reminders'] = $reminders;

        return $data;
    }

    /**
     * Formats the conditions for the root condition
     *
     * @param mixed $condition
     *
     * @return string
     */
    protected function _recursiveConditionLog($condition)
    {
        $conditions = array();

        foreach ($condition->getConditions() as $childCondition) {
            $html = str_replace('&nbsp;', '', $childCondition->asHtml());
            $html = preg_replace('|<select[^>]*>.*?</select>|s', '', $html);
            $html = preg_replace('|\s+|s', ' ', strip_tags($html));

            $conditions[] = array(
                'label'      => trim($html),
                'conditions' => $this->_recursiveConditionLog($childCondition),
            );
        }

        return $conditions;
    }
}
