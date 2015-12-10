<?php
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup|Mage_Core_Model_Mysql4_Setup */

$installer->startSetup();

try {
    $installer->run("
    ALTER TABLE `{$installer->getTable('bronto_reminder/rule')}`
    ADD COLUMN `send_limit` int(10) DEFAULT 1;
    ");
} catch (Exception $e) {
    Mage::helper('bronto_reminder')->writeError($e->getMessage());
}

try {
    $installer->run("TRUNCATE `{$installer->getTable('bronto_reminder/coupon')}`;");

    $installer->run("
    ALTER TABLE `{$installer->getTable('bronto_reminder/coupon')}`
    CHANGE COLUMN `unique_id` `unique_id` varchar(255) CHARACTER SET utf8 NOT NULL,
    CHANGE COLUMN `visitor_id` `customer_email` varchar(255) CHARACTER SET utf8 NOT NULL,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`rule_id`,`unique_id`,`store_id`,`customer_email`);
    ");
} catch (Exception $e) {
    Mage::helper('bronto_reminder')->writeError($e->getMessage());
}

try {
    $installer->run("
    ALTER TABLE `{$installer->getTable('bronto_reminder/log')}`
    CHANGE COLUMN `unique_id` `unique_id` varchar(255) CHARACTER SET utf8 NOT NULL;
    ");
} catch (Exception $e) {
    Mage::helper('bronto_reminder')->writeError($e->getMessage());
}

$installer->endSetup();
