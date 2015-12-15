<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("
    DROP TABLE IF EXISTS `{$this->getTable('bronto_newsletter_queue')}`;
        
    CREATE TABLE `{$this->getTable('bronto_newsletter_queue')}` (
      `queue_id` int(11) NOT NULL AUTO_INCREMENT,
      `subscriber_id` int(11) NOT NULL,
      `store` tinyint(4) NOT NULL,
      `status` varchar(32) CHARACTER SET utf8 NOT NULL,
      `message_preference` varchar(16) CHARACTER SET utf8 NOT NULL,
      `source` varchar(16) CHARACTER SET utf8 NOT NULL,
      `imported` tinyint(4) NOT NULL DEFAULT '0',
      `subscriber_email` varchar(255) CHARACTER SET utf8 NOT NULL,
      PRIMARY KEY (`queue_id`,`subscriber_id`,`store`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
");

} catch (Exception $e) {
    throw new RuntimeException('Failed Creating Newsletter Queue Table: ' . $e->getMessage());
}

$installer->endSetup();
