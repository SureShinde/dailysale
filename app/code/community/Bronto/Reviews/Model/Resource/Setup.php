<?php

class Bronto_Reviews_Model_Resource_Setup extends Bronto_Common_Model_Resource_Abstract
{
    /**
     * @see parent
     */
    protected function _module()
    {
        return 'bronto_reviews';
    }

    /**
     * @see parent
     */
    protected function _tables()
    {
        return array(
            'log' => "
            CREATE TABLE `{table}` (
                `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `order_id` int(10) unsigned NOT NULL,
                `order_increment_id` varchar(255) NOT NULL,
                `post_id` int(10) unsigned DEFAULT NULL,
                `post_name` varchar(50) NOT NULL,
                `product_id` int(10) unsigned DEFAULT NULL,
                `product_name` varchar(255) DEFAULT NULL,
                `store_id` int(10) unsigned NOT NULL,
                `delivery_id` varchar(36) DEFAULT NULL,
                `customer_email` varchar(255) NOT NULL,
                `message_id` varchar(36) DEFAULT NULL,
                `message_name` varchar(64) DEFAULT NULL,
                `delivery_date` datetime DEFAULT NULL,
                `cancelled` smallint(1) DEFAULT '0',
                `fields` text DEFAULT NULL,
                PRIMARY KEY (`log_id`),
                KEY `IDX_BRONTO_POSTPURCHASE_STORECANEMAIL` (`store_id`, `customer_email`, `cancelled`),
                KEY `IDX_BRONTO_POSTPURCHASE_ORDERDELIVERY` (`order_id`, `delivery_id`),
                KEY `IDX_BRONTO_POSTPURCHASE_POSTCAN` (`post_id`, `cancelled`),
                KEY `IDX_BRONTO_POSTPURCHASE_ORDERPOST` (`order_id`, `post_id`),
                KEY `IDX_BRONTO_POSTPURCHASE_STOREID` (`store_id`),
                KEY `IDX_BRONTO_POSTPURCHASE_CANCELED` (`cancelled`),
                KEY `IDX_BRONTO_POSTPURCHASE_EMAIL` (`customer_email`),
                KEY `IDX_BRONTO_POSTPURCHASE_DATE` (`delivery_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            'post_purchase' => "
            CREATE TABLE `{table}` (
                `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Re-Order ID',
                `active` smallint(1) unsigned NOT NULL DEFAULT '0',
                `product_id` int(10) unsigned NOT NULL COMMENT 'Magento Product ID',
                `post_type` varchar(30) NOT NULL DEFAULT 'reorder' COMMENT 'The type of reorder',
                `store_id` int(10) unsigned NOT NULL DEFAULT '0',
                `message` varchar(36) DEFAULT NULL,
                `send_limit` int(2) DEFAULT NULL,
                `multiply_by_qty` smallint(1) unsigned DEFAULT NULL,
                `adjustment` int(10) DEFAULT NULL,
                `content` text DEFAULT '',
                `period` int(10) unsigned DEFAULT NULL COMMENT 'Number of time periods',
                `period_type` varchar(20) DEFAULT 'daily' COMMENT 'The type of periods',
                PRIMARY KEY (`entity_id`),
                KEY `IDX_BRONTO_MAGENTO_ALLPRODUCTS` (`product_id`),
                KEY `IDX_BRONTO_MAGENTO_ACTIVEPRODUCTS` (`product_id`, `store_id`, `active`),
                KEY `IDX_BRONTO_MAGENTO_SCOPEDPRODUCTS` (`product_id`, `store_id`, `post_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Product Reorder Info'
            ");
    }

    /**
     * @see parent
     */
    protected function _updates()
    {
        return array(
          '0.1.0' => array(
              'post_purchase' => array(
                  'sql' =>
                  'ALTER TABLE {table} ADD COLUMN `multiply_by_qty` smallint(1) unsigned DEFAULT NULL AFTER `send_limit`;'
              )
          )
        );
    }

    /**
     * Sets the the email sender the cutom email
     */
    public function setCustomEmail()
    {
        $path = Bronto_Reviews_Helper_Data::XML_PATH_SENDER_EMAIL;
        $configData = Mage::getModel('core/config_data')
            ->getCollection()
            ->addFieldToFilter('path', array('eq' => $path));
        foreach ($configData as $config) {
            if (!is_null($config->getValue())) {
                Mage::getModel('core/config_data')
                    ->setPath(Bronto_Reviews_Helper_Data::XML_PATH_EMAIL_IDENTITY)
                    ->setScope($config->getScope())
                    ->setScopeId($config->getScopeId())
                    ->setValue('custom')
                    ->save();
            }
        }
    }
}
