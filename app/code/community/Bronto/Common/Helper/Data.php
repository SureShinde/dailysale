<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Helper_Data
    extends Mage_Core_Helper_Abstract
{
    const MAX_TOKEN_LENGTH = 36;

    /**
     * Common Settings
     */
    const XML_PATH_GLOBAL_SETTINGS = 'bronto/settings/';
    const XML_PATH_API_TOKEN       = 'bronto/settings/api_token';
    const XML_PATH_DEBUG           = 'bronto/settings/debug';
    const XML_PATH_VERBOSE         = 'bronto/settings/verbose';
    const XML_PATH_TEST            = 'bronto/settings/test';
    const XML_PATH_NOTICES         = 'bronto/settings/notices';
    const XML_PATH_ENABLED         = 'bronto/settings/enabled';
    const XML_PATH_TABLE_RUN       = 'bronto/settings/fix_script';

    /**
     * Formatting Settings
     */
    const XML_PATH_IMAGE_TYPE     = 'bronto/format/image_type';
    const XML_PATH_IMAGE_WIDTH    = 'bronto/format/image_width';
    const XML_PATH_IMAGE_HEIGHT   = 'bronto/format/image_height';
    const XML_PATH_USE_SYMBOL     = 'bronto/format/use_symbol';
    const XML_PATH_INCL_TAX       = 'bronto/format/incl_tax';
    const XML_PATH_GREETING_FULL  = 'bronto/format/default_greeting';
    const XML_PATH_GREETING_PRE   = 'bronto/format/default_greeting_prefix';
    const XML_PATH_GREETING_FIRST = 'bronto/format/default_greeting_firstname';
    const XML_PATH_GREETING_LAST  = 'bronto/format/default_greeting_lastname';

    /**
     * Cron Settings
     */
    const XML_PATH_MAGE_CRON   = 'bronto/settings/mage_cron';
    const XML_PATH_CRON_STRING = 'crontab/jobs/bronto_common_delete_archives/schedule/cron_expr';
    const XML_PATH_CRON_MODEL  = 'crontab/jobs/bronto_common_delete_archives/run/model';

    /**
     * Pop-up Settings
     */
    const XML_PATH_POPUP_CODE      = 'bronto_popup/settings/code';
    const XML_PATH_POPUP_SUBSCRIBE = 'bronto_popup/settings/subscribe';

    /**
     * Coupon Settings
     */
    const XML_PATH_COUPON_SITE_HASH = 'bronto_coupon/settings/site_hash';

    /**
     * Module Human Readable Name
     */
    protected $_name = 'Bronto Extension for Magento';

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
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_ENABLED, $scope, $scopeId);
    }

    /*
     * Get Text to display in notice when enabling module
     *
     * @return string
     */
    public function getModuleEnabledText()
    {
        return $this->__('If you have changed your API token, please ensure you reconfigure all available options.');
    }

    /**
     * Determines if the last time the table schema scan was a previous version
     *
     * @return bool
     */
    public function shouldRunFixScript()
    {
      return $this->getModuleVersion() != $this->getAdminScopedConfig(
          self::XML_PATH_TABLE_RUN,
          'default', $scopeId = 0);
    }

    /**
     * Get Javascript for Pop-up
     *
     * @return string
     */
    public function getPopupCode()
    {
        return $this->getAdminScopedConfig(self::XML_PATH_POPUP_CODE);
    }

    /**
     * Can the user be subscribed to magento?
     *
     * @return bool
     */
    public function isSubscribeToMagento()
    {
        return (bool) $this->getAdminScopedConfig(self::XML_PATH_POPUP_SUBSCRIBE);
    }

    /**
     * Get Site has for the coupon redemption code
     *
     * @return string
     */
    public function getCouponSiteHash()
    {
        return $this->getAdminScopedConfig(self::XML_PATH_COUPON_SITE_HASH);
    }

    /**
     * Determine if email can be sent through bronto
     *
     * @param Mage_Core_Model_Email_Template $template
     * @param string|int                     $storeId
     *
     * @return boolean
     */
    public function canSendBronto(Mage_Core_Model_Email_Template $template, $storeId = null)
    {
        if ($this->isEnabled('store', $storeId)) {
            return true;
        }

        return false;
    }

    /**
     * Get Image URL for Product, sized to config specs
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return string
     */
    public function getProductImageUrl($product)
    {
        try {
            return (string)Mage::helper('catalog/image')
                ->init($product, $this->getImageType($product->getStoreId()))
                ->resize(
                    $this->getImageWidth($product->getStoreId()),
                    $this->getImageHeight($product->getStoreId())
                );
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @param  string|int $storeId
     *
     * @return string
     */
    public function getImageType($storeId = null)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_IMAGE_TYPE, 'store', $storeId);
    }

    /**
     * @param  string|int $storeId
     *
     * @return int|null
     */
    public function getImageWidth($storeId = null)
    {
        $width = (int)$this->getAdminScopedConfig(self::XML_PATH_IMAGE_WIDTH, 'store', $storeId);

        return empty($width) ? null : abs($width);
    }

    /**
     * @param  string|int $storeId
     *
     * @return int|null
     */
    public function getImageHeight($storeId = null)
    {
        $height = (int)$this->getAdminScopedConfig(self::XML_PATH_IMAGE_HEIGHT, 'store', $storeId);

        return empty($height) ? null : abs($height);
    }

    /**
     * @param string|int $storeId
     *
     * @return bool
     */
    public function useCurrencySymbol($storeId = null)
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_USE_SYMBOL, 'store', $storeId);
    }

    /**
     * @param mixed $storeId
     *
     * @return bool
     */
    public function displayPriceIncTax($storeId = null)
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_INCL_TAX, 'store', $storeId);
    }

    /**
     * Get Default Greeting Settings
     *
     * @param string $piece
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getDefaultGreeting($piece = 'full', $scope = 'default', $scopeId = 0)
    {
        switch ($piece) {
            case 'prefix':
                return $this->getAdminScopedConfig(self::XML_PATH_GREETING_PRE, $scope, $scopeId);
            case 'firstname':
                return $this->getAdminScopedConfig(self::XML_PATH_GREETING_FIRST, $scope, $scopeId);
            case 'lastname':
                return $this->getAdminScopedConfig(self::XML_PATH_GREETING_LAST, $scope, $scopeId);
            case 'full':
            default:
                return $this->getAdminScopedConfig(self::XML_PATH_GREETING_FULL, $scope, $scopeId);
        }
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
     * @param string $path
     * @param string $scope
     * @param int    $scopeId
     * @param bool   $deleteConfig
     *
     * @return bool
     */
    protected function _disableModule($path, $scope = 'default', $scopeId = 0, $deleteConfig = false)
    {
        if ($scope == 'website' || $scope == 'store') {
            $scope .= 's';
        }

        $coreConfig     = Mage::getModel('core/config');
        $coreConfigData = Mage::getModel('core/config_data');

        // If set, we delete the config value instead of just setting it to 0
        if ($deleteConfig) {
            $coreConfig->deleteConfig($path, $scope, $scopeId);

            $coreConfigData
                ->load($path)
                ->setScope($scope)
                ->setScopeId($scopeId)
                ->delete();
        } else {
            $coreConfig->saveConfig($path, 0, $scope, $scopeId);

            if (!$this->isVersionMatch(Mage::getVersionInfo(), 1, array(4, 5, array('edition' => 'Professional', 'major' => 9), 10))) {
                list($module) = explode('/', $path);

                $coreConfigData->setScope($scope)
                    ->setScopeId($scopeId)
                    ->setPath("$module/settings/enabled")
                    ->setValue(0)
                    ->save();
            }
        }

        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();

        return $this;
    }

    /**
     * Determine if module is active
     *
     * @return boolean
     */
    public function isModuleActive()
    {
        // If module is not enabled, return false
        if (!$this->isEnabled()) {
            return false;
        }

        // If requirements are not met, return false
        if (!$this->verifyRequirements($this->_getModuleName())) {
            return false;
        }

        return true;
    }

    /**
     * Does this helper have custom config?
     *
     * @return boolean
     */
    public function hasCustomConfig()
    {
        return false;
    }

    /**
     * @deprecated since version 1.6.7
     * @see        verifyRequirements
     */
    public function varifyRequirements($module, $required = array())
    {
        return $this->verifyRequirements($module, $required);
    }

    /**
     * Verify that all required PHP extensions are loaded
     *
     * @param string $module
     * @param array  $required
     *
     * @return boolean
     */
    public function verifyRequirements($module, $required = array())
    {
        // Check for required PHP extensions
        $verified        = true;
        $missing         = array();
        $defaultRequired = array('soap', 'openssl');
        $required        = array_merge($required, $defaultRequired);
        $module          = strtolower($module);

        /*
         * Run through PHP extensions to see if they are loaded
         * if no, add them to the list of missing and set verified = false flag
         */
        foreach ($required as $extName) {
            try {
                if (!extension_loaded($extName)) {
                    $missing[] = $extName;
                    $verified  = false;
                }
            } catch (Exception $e) {
                $missing[] = $extName;
                $verified  = false;
            }
        }

        // If not verified, create a message telling the user what they are missing
        if (!$verified) {
            // If module is enabled, disable it
            if (Mage::helper($module)->isEnabled()) {
                Mage::helper($module)->disableModule();
            }
            // Create message informing of missing extensions
            $message = Mage::getSingleton('core/message')->error(
                $this->__(
                    sprintf(
                        'The module "' .
                        $module .
                        '" has been automatically disabled due to missing PHP extensions: %s',
                        implode(',', $missing)
                    )
                )
            );
            $message->setIdentifier($module);
            Mage::getSingleton('adminhtml/session')->addMessage($message);

            return false;
        }

        return true;
    }

    /**
     * Get Token Instance
     *
     * @param null   $token
     * @param string $scope
     * @param int    $scopeId
     *
     * @return Bronto_Common_Model_Api
     */
    public function getApi($token = null, $scope = 'default', $scopeId = 0)
    {
        if (empty($token)) {
            $token = $this->getApiToken($scope, $scopeId);
        }

        return Mage::getModel('bronto_common/api')
            ->setToken($token)
            ->getClient();
    }

    /**
     * Get API Token from Config
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return bool|mixed
     */
    public function getApiToken($scope = 'default', $scopeId = 0)
    {
        $token = $this->getAdminScopedConfig(self::XML_PATH_API_TOKEN, $scope, $scopeId);

        if (!$token || empty($token) || is_null($token) || $token == 'NULL') {
            return false;
        }

        return $token;
    }

    /**
     * Determine if API token is valid
     *
     * @param null   $token
     * @param string $scope
     * @param int    $scopeId
     *
     * @return bool
     */
    public function validApiToken($token = null, $scope = 'default', $scopeId = 0)
    {
        // If token is specifically set to false, then there is no token and is technically valid
        if (false === $token) {
            return true;
        }

        // If token is empty try to pull from config
        if (empty($token)) {
            $token = $this->getApiToken($scope, $scopeId);
        }

        if (strlen($token) < Bronto_Common_Helper_Data::MAX_TOKEN_LENGTH) {
            return false;
        }

        try {
            $api = $this->getApi($token, $scope, $scopeId);
            $tokenRow = $api->getTokenInfo();

            return $tokenRow->hasPermissions(7);
        } catch (Exception $e) {
            $helper = Mage::helper('bronto_common/api');
            if (
                !$helper->isStreamContextOverride() &&
                (
                    $e->getCode() == Bronto_Api_Exception::WSDL_PARSE_ERROR ||
                    $e->getCode() == Bronto_Api_Exception::CONNECTION_RESET
                )
            ) {
                $helper->setStreamContext(true);
                return $this->validApiToken($token, $scope, $scopeId);
            } else {
                if ($helper->isStreamContextOverride()) {
                    $helper->setStreamContext(false);
                }
                return false;
            }
        }
    }

    /**
     * Determines if the last API token used is in a valid state at the current
     * scope.
     *
     * @return bool
     */
    public function validApiStatus()
    {
        if (!Mage::helper('bronto_verify/apitoken')->getStatus()) {
            $this->_addSingleSessionMessage(
                'error',
                'The Bronto API Token you have entered for this scope appears to be invalid.'
            );
            return false;
        }

        return true;
    }

    /**
     * Adds a single message to the session, as to not flood the
     * session messages with the same content
     *
     * @param $type string
     * @param $msg string
     * @param $module string (optional)
     * @return boolean
     */
    protected function _addSingleSessionMessage($type, $msg, $module = 'bronto_common')
    {
        $message = Mage::getSingleton('core/message')->{$type}($this->__($msg));
        $message->setIdentifier($module);
        $session = Mage::getSingleton('adminhtml/session');
        foreach ($session->getMessages()->getItemsByType($type) as $set) {
            if ($set->getIdentifier() == $message->getIdentifier()) {
                $exists = true;
                break;
            }
        }

        if (empty($exists)) {
            $session->addMessage($message);
        }

        return empty($exists);
    }

    /**
     * Check all API tokens are valid
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function validApiTokens($identifier = 'bronto_common')
    {
        $valid = true;
        if (!$this->validApiToken($this->getApiToken())) {
            $message = Mage::getSingleton('core/message')->error(
                $this->__('The Bronto API Token you have entered for Default Configuration appears to be invalid.')
            );
            $message->setIdentifier($identifier);
            Mage::getSingleton('adminhtml/session')->addMessage($message);
            $valid = false;
        }
        foreach (Mage::app()->getWebsites() as $website) {
            if (!$this->validApiToken($this->getApiToken('website', $website->getId()), 'website', $website->getId())) {
                $message = Mage::getSingleton('core/message')->error(
                    $this->__(
                        sprintf(
                            'The Bronto API Token you have entered for website "%s" appears to be invalid.',
                            $website->getName()
                        )
                    )
                );
                $message->setIdentifier($identifier);
                Mage::getSingleton('adminhtml/session')->addMessage($message);
                $valid = false;
            }
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                if (count($stores) > 0) {
                    foreach ($stores as $store) {
                        if (!$this->validApiToken($this->getApiToken('store', $store->getId()), 'store', $store->getId())) {
                            $message = Mage::getSingleton('core/message')->error(
                                $this->__(
                                    sprintf(
                                        'The Bronto API Token you have entered for store "%s" on website "%s" appears to be invalid.',
                                        $store->getName(),
                                        $website->getName()
                                    )
                                )
                            );
                            $message->setIdentifier($identifier);
                            Mage::getSingleton('adminhtml/session')->addMessage($message);
                            $valid = false;
                        }
                    }
                }
            }
        }

        return $valid;
    }

    /**
     * @param string $moduleName
     *
     * @return bool
     */
    public function isModuleInstalled($moduleName = null)
    {
        $modules = (array)Mage::getConfig()->getNode('modules')->children();

        if ($moduleName === null) {
            $moduleName = $this->_getModuleName();
        }

        if (!isset($modules[$moduleName])) {
            return false;
        }

        return ($modules[$moduleName]->active == 'true');
    }

    /**
     * @param string $moduleName
     *
     * @return string
     */
    public function getModuleVersion($moduleName = null)
    {
        $modules = (array)Mage::getConfig()->getNode('modules')->children();

        if ($moduleName === null) {
            $moduleName = $this->_getModuleName();
        }

        return isset($modules[$moduleName]) ? (string)$modules[$moduleName]->version : null;
    }

    /**
     * @return bool
     */
    public function isDebugEnabled()
    {
        if (!$this->getApiToken()) {
            return false;
        }

        return (bool)$this->getAdminScopedConfig(self::XML_PATH_DEBUG);
    }

    /**
     * @return bool
     */
    public function isVerboseEnabled()
    {
        if (!$this->isDebugEnabled()) {
            return false;
        }

        return (bool)$this->getAdminScopedConfig(self::XML_PATH_VERBOSE);
    }

    /**
     * @return bool
     */
    public function isNoticesEnabled()
    {
        if (!$this->getApiToken()) {
            return false;
        }

        return (bool)$this->getAdminScopedConfig(self::XML_PATH_NOTICES);
    }

    /**
     * Write message to Debug log
     *
     * @param mixed $message
     * @param null  $file
     * @param bool  $verbose
     *
     * @return bool|void
     */
    public function writeDebug($message, $file = null, $verbose = false)
    {
        if ($verbose && !$this->isVerboseEnabled()) {
            return false;
        }

        if ($this->isDebugEnabled()) {
            return $this->writeLog($message, $file, Zend_Log::DEBUG);
        }

        return false;
    }

    /**
     * @param string      $message
     * @param string|null $file
     *
     * @return bool|void
     */
    public function writeVerboseDebug($message, $file = null)
    {
        if ($this->isVerboseEnabled()) {
            return $this->writeDebug($message, $file, true);
        }

        return false;
    }

    /**
     * @param string      $message
     * @param string|null $file
     *
     * @return bool|void
     */
    public function writeInfo($message, $file = null)
    {
        if ($this->isNoticesEnabled()) {
            if (Mage::getSingleton('admin/session')->isLoggedIn()) {
                /* @var $message Mage_Core_Model_Message_Notice */
                $sessionMessage = Mage::getSingleton('core/message')->notice("[Bronto] {$message}");
                Mage::getSingleton('adminhtml/session')->addMessage($sessionMessage);
            } else {
                Mage::getSingleton('core/session')->addNotice("[Bronto] {$message}");
            }
        }

        return $this->writeLog($message, $file, Zend_Log::INFO);
    }

    /**
     * @param Exception|string $message
     * @param string|null      $file
     *
     * @return bool|void
     */
    public function writeError($message, $file = null)
    {
        if (is_object($message) && $message instanceOf Exception) {
            $message = $message->getMessage();
        }
        if ($this->isNoticesEnabled()) {
            if (Mage::getSingleton('admin/session')->isLoggedIn()) {
                /* @var $message Mage_Core_Model_Message_Error */
                $message = Mage::getSingleton('core/message')->error("[Bronto] {$message}");
                Mage::getSingleton('adminhtml/session')->addMessage($message);
            } else {
                Mage::getSingleton('core/session')->addError("[Bronto] {$message}");
            }
        }

        return $this->writeLog($message, $file, Zend_Log::ERR);
    }

    /**
     * @param string      $message
     * @param string|null $file
     * @param int         $level
     *
     * @return bool|void
     */
    public function writeLog($message, $file = null, $level = Zend_Log::DEBUG)
    {
        if (empty($file)) {
            $file = strtolower($this->_getModuleName()) . '.log';
        }
        if (!is_string($message)) {
            if (method_exists($message, '__toString')) {
                $message = $message->__toString();
            } else {
                return false;
            }
        }

        return Mage::log($message, $level, $this->_stampFile($file), true);
    }

    /**
     * Add Date Stamp to log file name
     *
     * @param      $filename
     * @param bool $withTime
     *
     * @return mixed
     */
    protected function _stampFile($filename, $withTime = true)
    {
        // Ensure var/log/bronto exists
        $logDir = Mage::getBaseDir('var') . DS . 'log' . DS . 'bronto';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // If time stamp requested, append
        if ($withTime) {
            $stamp    = date('Ymd', time());
            $filename = str_replace('.', ".{$stamp}.", $filename);
        }

        // replace bronto_ with bronto/ to place in folder
        return str_replace('bronto_', 'bronto' . DS, $filename);
    }

    /**
     * Get list of active custom modules
     *
     * @param bool $brontoOnly
     *
     * @return array
     */
    public function getInstalledModules($brontoOnly = false)
    {
        $moduleList = array();
        $modules    = Mage::getConfig()->getNode('modules')->children();

        foreach ($modules as $name => $module) {
            if ($brontoOnly) {
                if (strpos($name, 'Bronto_') !== false && $module->active == 'true') {
                    $moduleList[] = strtolower($name);
                }
            } else if (strpos($name, 'Mage_') === false && strpos($name, 'Enterprise_') === false &&
                $module->active == 'true'
            ) {
                $moduleList[] = $name . ' [v' . $module->version . ' codePool: ' . $module->codePool . ']';
            }
        }

        return $moduleList;
    }

    /**
     * Get array of current scope parameters
     *
     * @return array
     */
    public function getScopeParams()
    {
        // Get Request Object
        $request = Mage::app()->getRequest();

        // Define Scope Params
        $scopeParams = array(
            'scope'      => 'default',
            'default'    => 0,
            'default_id' => 0,
            'store'      => $request->getParam('store', false),
            'store_id'   => 0,
            'website'    => $request->getParam('website', false),
            'website_id' => 0,
            'group'      => $request->getParam('group', false),
            'group_id'   => 0,
        );

        // Update Scope based on what has been set
        if ($scopeParams['store'] !== false) {
            $store = Mage::app()->getStore($scopeParams['store']);
            if ($store->getId()) {
                $scopeParams['store_id'] = $store->getId();
            }
            $scopeParams['scope'] = 'store';
        } elseif ($scopeParams['website'] !== false) {
            $website = Mage::app()->getWebsite($scopeParams['website']);
            if ($website->getId()) {
                $scopeParams['website_id'] = $website->getId();
            }
            $scopeParams['scope'] = 'website';
        } elseif ($scopeParams['group'] !== false) {
            $group = Mage::app()->getGroup($scopeParams['group']);
            if ($group->getId()) {
                $scopeParams['group_id'] = $group->getId();
            }
            $scopeParams['scope'] = 'group';
        }

        // Return array of Scope Params
        return $scopeParams;
    }

    /**
     * Get Url with scope data included
     *
     * @param       $url
     * @param array $scopeParams
     *
     * @return mixed
     */
    public function getScopeUrl($url, $scopeParams = array())
    {
        $curScopeParams = $this->getScopeParams();
        $curScope       = array(
            'scope'                  => $curScopeParams['scope'],
            $curScopeParams['scope'] => $curScopeParams[$curScopeParams['scope']],
        );

        if (array_key_exists('scope', $scopeParams)) {
            if ($scopeParams['scope'] != $curScope['scope']) {
                unset($curScope[$curScope['scope']]);
            }

            unset($scopeParams['scope']);
        }
        unset($curScope['scope']);

        $scopeParams = array_merge($scopeParams, $curScope);

        return Mage::helper('adminhtml')->getUrl($url, $scopeParams);
    }

    /**
     * Get Scoped Config Data
     *
     * @param        $path
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getAdminScopedConfig($path, $scope = 'default', $scopeId = 0)
    {
        if ('store' == $scope) {
            return Mage::getStoreConfig($path, $scopeId);
        } elseif ('website' == $scope) {
            $website = Mage::app()->getWebsite($scopeId);

            return $website->getConfig($path);
        }

        $scopeParams = $this->getScopeParams();

        switch ($scopeParams['scope']) {
            case 'store':
                $source = Mage::app()->getStore($scopeParams['store']);
                break;
            case 'website':
                $source = Mage::app()->getWebsite($scopeParams['website']);
                break;
            case 'group':
                $source = Mage::app()->getGroup($scopeParams['group'])->getWebsite();
                break;
            default:
                return Mage::getStoreConfig($path);
                break;
        }

        if ($source) {
            return $source->getConfig($path);
        }

        return Mage::getStoreConfig($path);
    }

    /**
     * Get Array of Store Ids based on current store/website/group
     *
     * @return boolean|array
     */
    public function getStoreIds()
    {
        $scopeParams = $this->getScopeParams();

        switch ($scopeParams['scope']) {
            case 'store':
                $source   = Mage::app()->getStore($scopeParams['store']);
                $storeIds = $source->getId();
                break;
            case 'website':
                $source   = Mage::app()->getWebsite($scopeParams['website']);
                $storeIds = $source->getStoreIds();
                break;
            case 'group':
                $source   = Mage::app()->getGroup($scopeParams['group'])->getWebsite();
                $storeIds = $source->getStoreIds();
                break;
            default:
                $storeIds = array_keys(Mage::app()->getStores(true));
                break;
        }

        return $storeIds;
    }

    /**
     * Is this the Enterprise edition?
     *
     * @return boolean
     */
    public function isEnterpriseEdition()
    {
        return ('Enterprise' == $this->getEdition());
    }

    /**
     * Is this the Professional edition?
     *
     * @return bool
     */
    public function isProfessionalEdition()
    {
        return ('Professional' == $this->getEdition());
    }

    /**
     * Get Edition from version Info
     *
     * @param  array|boolean $versionInfo
     *
     * @return string|boolean
     */
    public function getEdition($versionInfo = false)
    {
        // Ensure we have version info
        if (!$versionInfo || !is_array($versionInfo)) {
            if (method_exists('Mage', 'getEdition')) {
                return Mage::getEdition();
            }
            $versionInfo = Mage::getVersionInfo();
        }

        // Get Edition from version
        if (array_key_exists('major', $versionInfo) && array_key_exists('minor', $versionInfo)) {
            $major = $versionInfo['major'];
            $minor = $versionInfo['minor'];

            if (1 == $major) {
                if ($minor < 9 || ($minor == 9 && method_exists('Mage', 'getEdition'))) {
                    return 'Community';
                } else if ($minor >= 9 && $minor < 11) {
                    return 'Professional';
                } else if ($minor >= 11) {
                    return 'Enterprise';
                }
            }
        }

        return false;
    }

    /**
     * Takes major and minor version info and determines if current magento install matches
     *
     * Uses magic method to get Arguments
     *
     * param array            $versionInfo
     * param int|string|array $major
     * param int|string|array $minor
     * param int|string|array $revision (Optional)
     * param int|string|array $patch    (Optional)
     * param string           $edition  (Optional)      'CE'|'Community'|'PE'|'Professional'|'EE'|'Enterprise'
     *
     * @return bool
     */
    public function isVersionMatch()
    {
        /**
         * Get arguments passed to function
         *
         * [0] = Magento Version Array (Required)
         * [1] = Compare Major Version (Optional)
         * [2] = Compare Minor Version (Optional)
         * [3] = Compare Revision Number (Optional)
         * [4] = Compare Patch Number (Optional)
         * [5] = Compare Edition (Optional)
         */
        $parts = $this->_mapVersionParts(func_get_args());

        // At least version info and one other
        if (!array_key_exists('versionInfo', $parts) || count($parts) < 2) {
            return false;
        }

        // Get Magento Version from passed arguments
        $mageVersion            = $parts['versionInfo'];
        $mageVersion['edition'] = $this->getEdition($mageVersion);
        unset($parts['versionInfo']);

        // Cycle through the elements of the magento version
        foreach ($mageVersion as $index => $mValue) {
            // If the compare value doesn't exist for this index, continue
            if (!isset($parts[$index])) {
                continue;
            }

            // Get compare value
            $value = $parts[$index];
            // Ensure Value is an array
            if (!is_array($value)) {
                $value = array($value);
            }

            // Cycle through compare value array to compare against 
            // current Magento version element
            $internalMatch = false;
            foreach ($value as $option) {
                $edition = false;
                $operator = '==';
                $compare  = $option;

                // If the current compare value is an array, 
                // get the operator and value provided
                if (is_array($option)) {
                    if (array_key_exists('edition', $option)) {
                        $edition = $option['edition'];
                        $compare = $option['major'];
                    } else {
                        list ($operator, $compare) = $option;
                    }
                }

                if ($index == 'edition') {
                    // handle possibility of initials being used
                    switch (strtoupper($compare)) {
                        case 'EE':
                            $compare = 'Enterprise';
                            break;
                        case 'CE':
                            $compare = 'Community';
                            break;
                        case 'PE':
                            $compare = 'Professional';
                            break;
                        default:
                            break;
                    }

                    // If response from getEdition matches compare edition
                    $internalMatch = ($mValue == $compare);
                } else {
                    // Use version_compare to compare the Magento version to the
                    // Current compare version using the provided operator
                    $internalMatch = version_compare($mValue, $compare, $operator);
                    if ($edition && $internalMatch) {
                        $internalMatch = ($mageVersion['edition'] == $edition);
                    }
                }

                if ($internalMatch) {
                    break;
                }
            }

            // If the internal Match flag hasn't been set to true, 
            // there is no match
            if (!$internalMatch) {
                return false;
            }
        }

        // If we haven't returned false yet, that means there is a match
        return true;
    }

    /**
     * Maps parts array to expected array
     *
     * @param  array $parts
     *
     * @return array
     */
    private function _mapVersionParts($parts)
    {
        // Parts must be array
        if (!is_array($parts)) {
            return false;
        }

        // Generate index map values
        $mapKeys = array(
            'versionInfo' => 0,
            'major'       => 1,
            'minor'       => 2,
            'revision'    => 3,
            'patch'       => 4,
            'edition'     => 5,
        );

        // Placeholder array
        $versionParts = array();

        // Cycle Through and map values as needed
        foreach ($mapKeys as $map => $index) {
            if (array_key_exists($index, $parts) && !is_null($parts[$index])) {
                $versionParts[$map] = $parts[$index];
            }
        }

        // Return Mapped Array
        return $versionParts;
    }
}
