<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("

    ALTER TABLE `{$installer->getTable('core/email_template')}`
    ADD COLUMN `store_id` int(10) NOT NULL default '1';

");

} catch (Exception $e) {
    Mage::log($e->getMessage());
}

$installer->endSetup();