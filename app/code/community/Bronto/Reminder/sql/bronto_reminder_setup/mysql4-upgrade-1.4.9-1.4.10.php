<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("

    ALTER TABLE `{$installer->getTable('bronto_reminder/guest')}`
    CHANGE COLUMN `first_name` `firstname` varchar(50) CHARACTER SET utf8 NOT NULL,
    CHANGE COLUMN `last_name` `lastname` varchar(50) CHARACTER SET utf8 NOT NULL;
    
    ");
} catch (Exception $e) {
    //
}

$installer->endSetup();