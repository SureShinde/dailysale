<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Helper_Data
    extends Bronto_Common_Helper_Data
    implements Bronto_Common_Helper_DataInterface
{
    const XML_PATH_ENABLED            = 'bronto_email/settings/enabled';
    const XML_PATH_USE_BRONTO         = 'bronto_email/settings/use_bronto';
    const XML_PATH_LOG_ENABLED        = 'bronto_email/settings/log_enabled';
    const XML_PATH_LOG_FIELDS_ENABLED = 'bronto_email/settings/log_fields_enabled';
    const XML_PATH_DEFAULT_COUPON     = 'bronto_email/settings/default_coupon';
    const XML_PATH_DEFAULT_REC        = 'bronto_email/settings/default_recommendation';
    const XML_PATH_DEFAULT_SEND_FLAG  = 'bronto_email/settings/default_send_flags';

    /**
     * Xml path to email template nodes
     */
    const XML_PATH_TEMPLATE_EMAIL = '//sections/*/groups/*/fields/*[source_model="adminhtml/system_config_source_email_template"]';

    /**
     * Module Human Readable Name
     */
    protected $_name = 'Bronto Transactional Emails';

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
     * Disable Module for specified Scope
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
     * Retrieve helper module name
     *
     * @return string
     */
    protected function _getModuleName()
    {
        return 'Bronto_Email';
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
                'you should un-check the `Use Website` or `Use Default` for ALL options in the <em>Assign Templates</em> group on this page ' .
                'and select the desired templates.'
            );
        }

        return $message;
    }

    /**
     * Gets the default rule id
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getDefaultRule($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_DEFAULT_COUPON, $scope, $scopeId);
    }

    /**
     * Gets the default product recommendation
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getDefaultRecommendation($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_DEFAULT_REC, $scope, $scopeId);
    }

    /**
     * Gets the default send flags
     *
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getDefaultSendFlags($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_DEFAULT_SEND_FLAG, $scope, $scopeId);
    }

    /**
     * Get Config setting for sending through bronto
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return bool
     */
    public function canUseBronto($scope = 'default', $scopeId = 0)
    {
        if (!$this->getApiToken($scope, $scopeId)) {
            return false;
        }

        return (bool)$this->getAdminScopedConfig(self::XML_PATH_USE_BRONTO, $scope, $scopeId);
    }

    /**
     * Sets the "Send through Bronto" option for any config scope
     *
     * @param        $brontoSend
     * @param string $scope
     * @param int    $scopeId
     *
     * @return $this
     */
    public function setUseBronto($brontoSend, $scope = 'default', $scopeId = 0)
    {
        $config = Mage::getModel('core/config');
        $config->saveConfig(self::XML_PATH_USE_BRONTO, $brontoSend ? '1' : '0', $scope, $scopeId);

        return $this;
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
        if (
            $this->isEnabled('store', $storeId) &&
            $this->canUseBronto('store', $storeId) &&
            !is_null($template->getBrontoMessageId()) &&
            $template->getTemplateSendType() != 'magento'
        ) {
            return true;
        }

        return false;
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
     * @see parent
     * @return bool
     */
    public function hasCustomConfig()
    {
        return true;
    }

    /**
     * Gets any saved emails, and reports it
     *
     * @return array
     */
    public function getCustomConfig($scope = 'default', $scopeId = 0)
    {
        $emails    = array();
        $templates = Mage::getModel('bronto_email/template')->getCollection();

        if ($this->isVersionMatch(Mage::getVersionInfo(), 1, array(4, 5, array('edition' => 'Professional', 'major' => 9), 10))) {
            $templateTable = Mage::getSingleton('core/resource')->getTableName('bronto_email/template');
            $brontoTable   = Mage::getSingleton('core/resource')->getTableName('bronto_email/message');
            $templates->getSelect()->joinLeft(
                $brontoTable,
                "`{$templateTable}`.`template_id` = `{$brontoTable}`.`core_template_id`"
            );
        }

        $templates->addFieldToFilter('bronto_message_id', array('notnull' => true));

        foreach ($templates as $template) {
            $emails[] = array(
                'template_id'         => $template->getTemplateId(),
                'template_code'       => $template->getTemplateCode(),
                'bronto_message_id'   => $template->getBrontoMessageId(),
                'bronto_message_name' => $template->getBrontoMessageName(),
                'send_type'           => $template->getTemplateSendType(),
            );
        }

        $settings = array();
        foreach ($this->getTemplatePaths() as $configPath) {
            $data = $this->getAdminScopedConfig($configPath, $scope, $scopeId);
            if (str_replace('/', '_', $configPath) == $data) {
                $data = 'Default';
            }
            $settings[$configPath] = $data;
        }

        return array(
            'templates' => $emails,
            'settings'  => $settings,
        );
    }

    /**
     * Get array of all template config paths
     *
     * @return array
     */
    public function getTemplatePaths()
    {
        $templatePaths = array();

        $configSections = Mage::getSingleton('adminhtml/config')->getSections();

        // look for node entries in all system.xml that use source_model=adminhtml/system_config_source_email_template
        // they are will be templates, what we try find
        $sysCfgNodes = $configSections->xpath(self::XML_PATH_TEMPLATE_EMAIL);
        if (!is_array($sysCfgNodes)) {
            return array();
        }

        foreach ($sysCfgNodes as $fieldNode) {

            $groupNode   = $fieldNode->getParent()->getParent();
            $sectionNode = $groupNode->getParent()->getParent();

            // create email template path in system.xml
            $sectionName = $sectionNode->getName();
            $groupName   = $groupNode->getName();
            $fieldName   = $fieldNode->getName();

            $templatePaths[] = implode('/', array($sectionName, $groupName, $fieldName));
        }

        return $templatePaths;
    }
}
