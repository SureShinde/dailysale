<?php

class Bronto_Email_Model_Resource_Setup extends Bronto_Common_Model_Resource_Abstract
{

    /**
     * @see parent
     */
    protected function _module()
    {
        return 'bronto_email';
    }

    /**
     * @see parent
     */
    protected function _tables()
    {
        return array(
        'message' => "
        CREATE TABLE `{table}` (
            `core_template_id` int(10) unsigned NOT NULL COMMENT 'Magento Template ID',
            `template_send_type` varchar(255) NOT NULL DEFAULT 'magento' COMMENT 'Type of message to send as',
            `orig_template_text` text COMMENT 'Original Template Text',
            `bronto_message_id` varchar(36) DEFAULT NULL COMMENT 'Bronto Message ID',
            `bronto_message_name` varchar(255) DEFAULT NULL COMMENT 'Bronto Message Name',
            `bronto_message_approved` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT 'Bronto Message Transactional Approval Status',
            `store_id` int(11) NOT NULL DEFAULT '1' COMMENT 'Store ID for Template',
            `sales_rule` int(10) NULL COMMENT 'Sales Rule for Coupon Codes',
            `product_recommendation` int(11) unsigned DEFAULT NULL COMMENT 'Product Recommendations',
            `send_flags` int(3) unsigned DEFAULT NULL COMMENT 'Delivery Flags',
            PRIMARY KEY (`core_template_id`),
            KEY `IDX_BRONTO_STORE_ID` (`store_id`),
            CONSTRAINT `FK_BRONTO_EMAIL_TEMPLATE_ID_CORE_EMAIL_TEMPLATE_ID` FOREIGN KEY (`core_template_id`)
            REFERENCES `{$this->getTable('core/email_template')}` (`template_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Email Template Attributes'
        ",
        'log' => "
        CREATE TABLE `{table}` (
            `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Log ID',
            `customer_id` int(10) unsigned DEFAULT NULL COMMENT 'Customer ID',
            `customer_email` varchar(255) DEFAULT NULL COMMENT 'Customer Email Address',
            `contact_id` varchar(36) DEFAULT NULL COMMENT 'Bronto Contact ID',
            `message_id` varchar(36) NOT NULL COMMENT 'Bronto Message ID',
            `message_name` varchar(64) DEFAULT NULL COMMENT 'Bronto Message Name',
            `delivery_id` varchar(36) DEFAULT NULL COMMENT 'Bronto Delivery ID',
            `sent_at` datetime DEFAULT NULL COMMENT 'Date Message Sent',
            `success` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Message Send Success',
            `error` varchar(255) DEFAULT NULL COMMENT 'Error Message',
            `fields` text COMMENT 'Fields',
            PRIMARY KEY (`log_id`),
            KEY `IDX_BRONTO_EMAIL_LOG_CUSTOMER_EMAIL` (`customer_email`),
            KEY `IDX_BRONTO_EMAIL_LOG_SENT_AT` (`sent_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Email Delivery Logs'
        ");
    }

    /**
     * @see parent
     */
    protected function _updates()
    {
        return array(
            '1.2.0' => array(
                'message' => array(
                    'sql' =>
                    "ALTER TABLE {table} ADD COLUMN `product_recommendation` int(11) unsigned DEFAULT NULL AFTER `sales_rule`;"
                )
            ),
            '1.2.1' => array(
                'message' => array(
                    'sql' =>
                    "ALTER TABLE {table} ADD COLUMN `send_flags` int(3) unsigned DEFAULT NULL AFTER `product_recommendation`;"
                )
            )
        );
    }

    /**
     * Sets the Bronto sending for all available scopes if the module is enabled
     *
     * @return Bronto_Email_Model_Resource_Setup
     */
    public function setDefaultSending()
    {
        $this->_reloadNewConfig()->_setDefaultSending()->_reloadNewConfig();
        foreach (Mage::app()->getWebsites() as $website) {
            $this->_setDefaultSending(null, $website->getId());
        }

        $this->_reloadNewConfig();
        foreach (Mage::app()->getStores() as $store) {
            $this->_setDefaultSending($store->getId());
        }

        return $this;
    }

    /**
     * @return Bronto_Email_Model_Resource_Setup
     */
    protected function _reloadNewConfig()
    {
        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();

        return $this;
    }

    /**
     * Sets the default sending to bronto is the module is enabled
     *
     * @param string|int $storeId
     * @param string|int $websiteId
     *
     * @return Bronto_Email_Model_Resource_Setup
     */
    protected function _setDefaultSending($storeId = null, $websiteId = null)
    {
        if (!is_null($storeId)) {
            $scope   = 'store';
            $scopeId = $storeId;
        } elseif (!is_null($websiteId)) {
            $scope   = 'website';
            $scopeId = $websiteId;
        } else {
            $scope   = 'default';
            $scopeId = 0;
        }
        $helper = Mage::helper('bronto_email');
        if (
            $helper->isEnabled($scope, $scopeId) &&
            !$helper->canUseBronto($scope, $scopeId)
        ) {
            $helper->setUseBronto(true, $scope, $scopeId);
        }

        return $this;
    }
}
