<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Mysql4_Setup */

$installer->startSetup();


try {
    $installer->run("DROP TABLE IF EXISTS `{$installer->getTable('bronto_reviews/queue')}`;");

    $installer->run("
        CREATE TABLE `{$installer->getTable('bronto_reviews/queue')}` (
            `order_id` int(10) unsigned NOT NULL COMMENT 'Magento Order ID',
            `delivery_id` varchar(36) DEFAULT NULL COMMENT 'Bronto Message ID',
            PRIMARY KEY (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto Review Request Deliveries'
    ");

} catch (Exception $e) {
    Mage::helper('bronto_reviews')->writeError($e->getMessage());
}

$installer->endSetup();
