<?php
/**
 * fall back to create table if existing modules already exists to support upgrade
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("
        CREATE TABLE IF NOT EXISTS `{$this->getTable('bronto_newsletter_queue')}` (
          `status` varchar(32) CHARACTER SET utf8 NOT NULL,
          `messagePreference` varchar(16) CHARACTER SET utf8 NOT NULL,
          `source` varchar(16) CHARACTER SET utf8 NOT NULL,
          `imported` tinyint(4) NOT NULL DEFAULT '0',
          `subscriber_id` int(11) NOT NULL AUTO_INCREMENT,
          `subscriber_email` varchar(255) CHARACTER SET utf8 NOT NULL,
          `store` tinyint(4) NOT NULL,
          PRIMARY KEY (`subscriber_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    ");

} catch (Exception $e) {
    throw new RuntimeException('Table Already Exists');
}

$installer->endSetup();
