<?php

$installer = $this;
/* @var $installer Bronto_Common_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("DROP TABLE IF EXISTS `{$installer->getTable('bronto_common/api')}`;");

    $installer->run("
      CREATE TABLE `{$installer->getTable('bronto_common/api')}` (
        `token` varchar(36) NOT NULL,
        `session_id` varchar(36) NOT NULL,
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`token`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto API Session table'
      ");

    $installer->run("DROP TABLE IF EXISTS `{$installer->getTable('bronto_common/error')}`;");

    $installer->run("
      CREATE TABLE `{$installer->getTable('bronto_common/error')}` (
        `error_id` int(11) NOT NULL AUTO_INCREMENT,
        `email_class` varchar(100) NULL,
        `object` text NOT NULL DEFAULT '',
        `attempts` smallint(1) NOT NULL,
        `last_attempt` datetime NOT NULL,
        PRIMARY KEY (`error_id`),
        KEY `IDX_BRONTO_ERROR_ATTEMPT` (`attempts`),
        KEY `IDX_BRONTO_ERROR_TIMESTAMP` (`last_attempt`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bronto API Error log'
      ");
} catch (Exception $e) {
    Mage::helper('bronto_common')->writeError('Failed to create API tables: ' . $e->getMessage());
}

$installer->endSetup();
