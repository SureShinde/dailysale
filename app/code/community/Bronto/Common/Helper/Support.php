<?php

class Bronto_Common_Helper_Support extends Bronto_Common_Helper_Data
{
    const XML_PATH_SUPPORT    = 'bronto/support';
    const XML_PATH_LAST_RUN   = 'bronto/support/last_run';
    const XML_PATH_REGISTERED = 'bronto/support/registered';

    const XML_PATH_CLEAR_LOGS = 'bronto/settings/clear_logs';
    const XML_PATH_LOG_THRES  = 'bronto/settings/log_threshold';

    // Process registration
    protected $_registrationUrl = 'https://brontops.com/register/magento';

    // Last time the support information was pushed
    protected $_lastRun;
    protected $_client;

    protected $_supportFormFields = array(
        'site_name',
        'firstname',
        'lastname',
        'email',
        'number',
        'title',
        'using_solution_partner',
        'partner',
        'terms',
        'magento_version',
        'magento_edition',
        'extension_version',
    );

    /**
     * @return bool
     */
    public function isRegistered()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_REGISTERED);
    }

    /**
     * @return bool
     */
    public function shouldClearLogs()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_CLEAR_LOGS);
    }

    public function getLogThreshold()
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_LOG_THRES) * (60 * 60 * 24);
    }

    /**
     * @param $onBronto boolean (optional)
     * @return boolean
     */
      public function verifyRegistration($onBronto = false) {
        if (!$this->isRegistered()) {
            $appendix = '<a href="#bronto_support-head">below</a>.';
            if (!$onBronto) {
                $registerUrl = Mage::getSingleton('adminhtml/url')
                    ->getUrl('*/system_config/edit', array('section' => 'bronto'));
                $appendix = '<a href="' . $registerUrl . '">here</a>.';
            }
            $this->_addSingleSessionMessage(
                'warning',
                'Please register your Bronto extension ' . $appendix
            );
            return false;
        }
        return true;
    }

    /**
     * @return int
     */
    public function getLastRunTimestamp()
    {
        if (is_null($this->_lastRun)) {
            $lastRun        = $this->getAdminScopedConfig(self::XML_PATH_LAST_RUN);
            $this->_lastRun = $lastRun ? $lastRun : 0;
        }

        return $this->_lastRun;
    }

    /**
     * Set the registration value for this extension
     *
     * @param boolean $register
     *
     * @return Mage_Core_Helper_Data
     */
    public function setRegistered($register = true)
    {
        $config = Mage::getModel('core/config');
        $config->saveConfig(self::XML_PATH_REGISTERED, $register ? '1' : '0', 'default', 0);

        // Force the register to pickup immediately
        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();

        return $this;
    }

    /**
     * Set the last run time for this extension
     *
     * @param string $date
     *
     * @return $this
     */
    public function setLastRunDate($date)
    {
        $this->_lastRun = Mage::getModel('core/date')->timestamp($date);

        $config = Mage::getModel('core/config');
        $config->saveConfig(self::XML_PATH_LAST_RUN, $this->_lastRun, 'default', 0);

        return $this;
    }

    /**
     * Retrieves some support information
     *
     * @return array
     */
    public function getSupportInformation()
    {
        $data = array();
        foreach ($this->_supportFormFields as $key) {
            switch ($key) {
                case 'extension_version':
                    $value = 'v' . $this->getModuleVersion();
                    break;
                case 'magento_version':
                    $value = 'v' . Mage::getVersion();
                    break;
                case 'magento_edition':
                    $value = $this->getEdition();
                    break;
                default:
                    $value = $this->getAdminScopedConfig(self::XML_PATH_SUPPORT . "/$key");
            }
            $data[$key] = $value ? $value : '';
        }

        return $data;
    }

    /**
     * Determines if this last run is a day old
     *
     * @param string $currentDate
     *
     * @return boolean
     */
    public function isLastRunDifferent($currentDate)
    {
        $lastRun = $this->getLastRunTimestamp();

        return $lastRun != Mage::getModel('core/date')->timestamp($currentDate);
    }

    /**
     * Gets the number of the reminder rules
     *
     * @param string $currentDate
     *
     * @return int
     */
    public function getActiveReminderRules($currentDate)
    {
        return Mage::getModel('bronto_reminder/rule')
            ->getCollection()
            ->addDateFilter($currentDate)
            ->addIsActiveFilter(1)
            ->count();
    }

    /**
     * Returns debug information as a collection
     *
     * @return array
     */
    public function getDebugInformation($scope = 'default', $scope = 0)
    {
        $currentDate   = Mage::getModel('core/date')->date('Y-m-d');
        $brontoModules = $this->getEnabledBrontoModules();
        $formData      = $this->getSupportInformation();
        $request       = Mage::app()->getRequest();

        return array_merge(
            // Form submission
            $formData,
            // Current Websites / Stores; Enabled Bronto Modules
            array(
                // Client / Server information
                'server_name'         => $request->getServer('SERVER_NAME'),
                'server_address'      => $request->getServer('SERVER_ADDR'),
                'server_protocol'     => $request->getServer('SERVER_PROTOCOL'),
                'php_version'         => 'v' . phpversion(),
                'mysql_version'       => 'v' . Mage::getResourceModel('core/config')->getReadConnection()->getServerVersion(),
                'number_active_rules' => $this->getActiveReminderRules($currentDate),
            ),
            array(
                // Installed Modules
                'installed_modules'    => $this->getInstalledModules(),
                'magento_installation' => $this->getStoreInfo(),
            ),
            array(
                'bronto_modules' => $brontoModules,
                'bronto_config'  => $this->getBrontoConfigs($brontoModules, $scope, $scopeId)
            )
        );
    }

    /**
     * Submits the Support form information
     *
     * @param array $formData (Optional)
     *
     * @return boolean
     */
    public function submitSupportForm($formData = array())
    {
        $currentDate = Mage::getModel('core/date')->date('Y-m-d');
        $this->setLastRunDate($currentDate)->setRegistered();

        $formData['extension_version'] = 'v' . $this->getModuleVersion();
        $formData['magento_version']   = 'v' . Mage::getVersion();
        $formData['magento_edition']   = $this->getEdition();

        $yesNo         = Mage::getModel('adminhtml/system_config_source_yesno');
        foreach (array('using_solution_partner', 'terms') as $formKey) {
            $selectedValue = $formData[$formKey];
            foreach ($yesNo->toOptionArray() as $option) {
                if ($option['value'] == $selectedValue) {
                    $formData[$formKey] = $option['label'];
                }
            }
        }

        return $this->_submitWebform(
            array_merge($this->getSupportInformation(), $formData)
        );
    }

    /**
     * Gets the Bronto Modules install on the server
     *
     * @return array
     */
    public function getEnabledBrontoModules()
    {
        $brontoModules = array();

        $modules = Mage::getConfig()->getNode('modules')->children();
        foreach ($modules as $name => $module) {
            if (
                $module->active == 'true' &&
                strpos($name, 'Bronto_') !== false &&
                (
                    $name == 'Bronto_Common' ||
                    Mage::helper(strtolower($name))->isEnabled()
                )
            ) {
                $brontoModules[$name] = 'v' . $module->version;
            }
        }

        return $brontoModules;
    }

    /**
     * Gets the Bronto configuration settings
     *
     * @param array $brontoModules
     * @param string $scope
     * @param int $scopeId
     *
     * @return array
     */
    public function getBrontoConfigs($brontoModules, $scope = 'default', $scopeId = 0)
    {
        $configs          = array();
        $processedConfigs = array();

        foreach ($brontoModules as $name => $module) {
            $helperName = strtolower($name);

            $helper    = Mage::helper($helperName);
            $reflector = new ReflectionClass(get_class($helper));

            $moduleConfig = array();
            foreach ($reflector->getConstants() as $cName => $setting) {
                if ($cName == 'XML_PATH_ENABLED' || isset($processedConfigs[$setting])) {
                    continue;
                }

                $settingNameParts = explode('/', $setting);
                $settingName      = end($settingNameParts);

                $value = $this->getAdminScopedConfig($setting, $scope, $scopeId);
                if (empty($settingName)) {
                    continue;
                }

                $processedConfigs[$setting] = $value;
                $moduleConfig[$settingName] = $value;
            }

            if ($helper->hasCustomConfig()) {
                $moduleConfig = array_merge($moduleConfig, $helper->getCustomConfig($scope, $scopeId));
            }

            if ($moduleConfig) {
                $configs["{$helperName}_config"] = $moduleConfig;
            }
        }

        return $configs;
    }

    /**
     * Retrieve website and store count
     *
     * @return array
     */
    public function getStoreInfo()
    {
        $storeInfo     = array();
        $websites      = Mage::app()->getWebsites();
        $totalWebsites = count($websites);
        $totalStores   = 0;
        foreach ($websites as $website) {
            $websiteStores = count($website->getStores());
            $totalStores += $websiteStores;
            $s           = $websiteStores == 1 ? '' : 's';
            $storeInfo[] = "A website with $websiteStores store$s.";
        }
        $websites = $totalWebsites == 1 ? 'website' : 'websites';
        $stores   = $totalStores == 1 ? 'store' : 'stores';

        $storeInfo[] = "Total of $totalWebsites $websites and $totalStores $stores";

        return $storeInfo;
    }

    /**
     * Creates a log archive with the last 30 days of files in it
     *
     * @return Bronto_Common_Model_Archive
     */
    public function getLogArchive()
    {
        $logDir       = Mage::getBaseDir('var') . DS . 'log';
        $systemLog    = $logDir . DS . 'system.log';
        $exceptionLog = $logDir . DS . 'exception.log';

        $brontoLogDir = $logDir . DS . 'bronto';
        $tmpDir       = $this->getArchiveDirectory();
        $file         = $tmpDir . DS . 'log.' . time() . '.zip';

        $archive = Mage::getModel('bronto_common/archive');
        if ($archive->open($file, ZipArchive::OVERWRITE)) {
            $now       = time();
            $threshold = $now - $this->getLogThreshold();

            $archive->addEmptyDir('log');

            if (file_exists($systemLog)) {
                $archive->addFromString('log/system.log', $this->_tailLog($systemLog));
            }

            if (file_exists($exceptionLog)) {
                $archive->addFromString('log/exception.log', $this->_tailLog($exceptionLog));
            }

            $archive->addFromString('log/phpinfo.html', $this->getPhpInfoOutput());
            $archive->addEmptyDir('log/bronto');
            foreach (glob($brontoLogDir . DS . "*.log") as $logFile) {
                $stat = lstat($logFile);
                if ($stat['mtime'] < $threshold) {
                    continue;
                }
                $archive->addFile($logFile, 'log/bronto/' . basename($logFile));
            }
            $archive->close();
        } else {
            Mage::throwException('Could not create archive at ' . $file);
        }

        return $archive;
    }

    /**
     * Removes log files that are over the threshold old
     *
     * @return bool
     */
    public function clearOldLogs()
    {
        $logDir = Mage::getBaseDir('var') . DS . 'log' . DS . 'bronto';
        $threshold = time() - $this->getLogThreshold();

        $success = true;
        foreach (glob($logDir . DS . '*log') as $logFile) {
            $stat = lstat($logFile);
            if ($stat['mtime'] < $threshold) {
                $success = $success && unlink($logFile);
            }
        }
        return $success;
    }

    /**
     * Tails a given log for output
     *
     * @param $logfile
     *
     * @return string
     */
    protected function _tailLog($logfile)
    {
        $length  = filesize($logfile);
        $maxRead = (1 * 1000 * 100);
        $fh      = fopen($logfile, 'r');
        if ($length > $maxRead) {
            fseek($fh, $length - $maxRead);
        }
        $contents = fread($fh, $maxRead);
        fclose($fh);

        return $contents;
    }


    /**
     * Gets the PHPInfo in string format
     *
     * @return string
     */
    public function getPhpInfoOutput()
    {
        ob_start();
        phpinfo();

        return ob_get_clean();
    }

    /**
     * Gets the archive directory
     *
     * @return string
     */
    public function getArchiveDirectory()
    {
        $brontoLogDir = Mage::getBaseDir('var') . DS . 'log' . DS . 'bronto';
        $tmpDir       = $brontoLogDir . DS . 'archives';
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        return $brontoLogDir . DS . 'archives';
    }

    /**
     * Deletes all of the archive logs
     */
    public function clearArchiveDirectory()
    {
        foreach (glob($this->getArchiveDirectory() . DS . '*') as $file) {
            unlink($file);
        }
    }

    /**
     * Sets the internal webform submission client
     *
     * @param Mage_HTTP_Client_Curl $client
     *
     * @return Bronto_Common_Helper_Support
     */
    public function setHttpClient(Mage_HTTP_Client_Curl $client)
    {
        $this->_client = $client;

        return $this;
    }

    /**
     * Returns the Curl client used to submit the webform
     *
     * @return Mage_HTTP_Client_Curl
     */
    protected function _getHttpClient()
    {
        if (empty($this->_client)) {
            $this->_client = new Mage_HTTP_Client_Curl();
        }

        return $this->_client;
    }

    /**
     * Submits a webform with the registration info
     *
     * @param array $formData
     *
     * @return bool
     */
    protected function _submitWebform(array $formData)
    {

        $client = $this->_getHttpClient();
        $params = array();
        foreach ($this->_supportFormFields as $name) {
            $parts     = explode('_', $name);
            $restCamel = array_map('ucfirst', array_slice($parts, 1));
            $camel     = implode('', array_merge(array($parts[0]), $restCamel));

            $params[$camel] = $formData[$name];
        }

        $json = Mage::helper('core')->jsonEncode($params);

        try {
            $client->setOptions(array(
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_POSTFIELDS     => $json
            ));
            $client->post($this->_registrationUrl, $params);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            $this->writeError('Registration submission failed: ', $e->getMessage());

            return false;
        }

        return true;
    }
}
