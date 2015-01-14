<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Model_Resource_Setup extends Bronto_Common_Model_Resource_Abstract
{

    /**
     * @see parent
     * @return string
     */
    protected function _module()
    {
        return 'bronto_common';
    }

    /**
     * Gets all of the create table definititions at this version
     *
     * @see parent
     * @return array
     */
    protected function _tables()
    {
        return array(
            'api' => "
            CREATE TABLE `{table}` (
              `token` varchar(36) NOT NULL,
              `session_id` varchar(36) NOT NULL,
              `created_at` datetime NOT NULL,
              PRIMARY KEY (`token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto API Session table'",
            'error' => "
            CREATE TABLE `{table}` (
              `error_id` int(11) NOT NULL AUTO_INCREMENT,
              `email_class` varchar(100) NULL,
              `object` mediumtext NOT NULL DEFAULT '',
              `attempts` smallint(1) NOT NULL,
              `last_attempt` datetime NOT NULL,
              PRIMARY KEY (`error_id`),
              KEY `IDX_BRONTO_ERROR_ATTEMPT` (`attempts`),
              KEY `IDX_BRONTO_ERROR_TIMESTAMP` (`last_attempt`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto API Error log'",
            'queue' => "
            CREATE TABLE `{table}` (
              `queue_id` int(11) NOT NULL AUTO_INCREMENT,
              `store_id` int(11) NOT NULL DEFAULT '1',
              `email_class` varchar(100) NOT NULL,
              `email_data` mediumtext NOT NULL DEFAULT '',
              `holding` smallint(1) NOT NULL DEFAULT 0,
              `created_at` datetime NOT NULL,
              PRIMARY KEY (`queue_id`),
              KEY `IDX_BRONTO_SEND_QUEUE_STORE` (`store_id`),
              KEY `IDX_BRONTO_SEND_QUEUE_HOLDING` (`holding`),
              KEY `IDX_BRONTO_SEND_QUEUE_TIMESTAMP` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto API Send queue'"
        );
    }

    /**
     * Updates the appropriate tables
     *
     * @see parent
     * @return array
     */
    protected function _updates()
    {
        return array(
            '2.3.0' => array(
                'error' => array(
                    'sql' => 'ALTER TABLE {table} MODIFY COLUMN `object` mediumtext'
                ),
                'queue' => array(
                    'before' => 'dropTable',
                    'after' => 'createTable',
                )
            )
        );
    }

    /**
     * Re-submits the registration information stored in core_config_data
     *
     * @return boolean
     */
    public function resubmitFormInfo()
    {
        $helper = Mage::helper('bronto_common/support');
        $prefix = Bronto_Common_Helper_Support::XML_PATH_SUPPORT . '/';
        $skippable = array(
            Bronto_Common_Helper_Support::XML_PATH_LAST_RUN,
            Bronto_Common_Helper_Support::XML_PATH_REGISTERED
        );
        $submittedData = Mage::getModel('core/config_data')->getCollection()
            ->addFieldToFilter('scope', array('eq' => 'default'))
            ->addFieldToFilter('path', array('like' => $prefix . '%'));
        $formData = array();
        foreach ($submittedData as $config) {
            if (in_array($config->getKey(), $skippable)) {
                continue;
            }
            $key = str_replace($prefix, '', $config->getPath());
            $formData[$key] = $config->getValue();
        }
        return $helper->submitSupportForm($formData);
    }

    public function handleOld()
    {
        // Look if Bronto folder exists in local codepool and recursively remove if it is
        $source      = Mage::getBaseDir('base') . DS . 'app' . DS . 'code' . DS . 'local' . DS . 'Bronto' . DS;
        $destination = Mage::getBaseDir('base') . DS . 'var' . DS . 'bronto_backup' . DS;
        if (file_exists($source)) {
            $this->rcopy($source, $destination);
            $this->rrmdir($source);

            // Add Notification so customer is sure to know
            Mage::getSingleton('adminnotification/inbox')->add(
                4,
                'Bronto Update - Old Version Moved',
                'Bronto has been updated.  We have moved the files from your previous installation to ' . $destination
            );
        }
    }

    public function rrmdir($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->rrmdir("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function rcopy($src, $dst)
    {
        // Remove Destination if it is a file
        if (file_exists($dst)) {
            $this->rrmdir($dst);
        }
        // If Source is a directory create destination and move everything
        if (is_dir($src)) {
            mkdir($dst);
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $this->rcopy("$src/$file", "$dst/$file");
                }
            }
        } elseif (file_exists($src)) {
            copy($src, $dst);
        }
    }
}
