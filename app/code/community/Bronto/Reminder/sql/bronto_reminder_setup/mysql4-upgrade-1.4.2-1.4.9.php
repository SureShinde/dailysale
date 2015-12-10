<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

try {
    $installer->run("

    CREATE TABLE IF NOT EXISTS `{$installer->getTable('bronto_reminder/guest')}` (
      `guest_email_id` int(11) NOT NULL AUTO_INCREMENT,
      `email_address` varchar(150) CHARACTER SET utf8 NOT NULL,
      `email_sent` tinyint(3) unsigned NOT NULL DEFAULT '0',
      `session_id` varchar(64) NOT NULL,
      `first_name` varchar(50) CHARACTER SET utf8 NOT NULL,
      `last_name` varchar(50) CHARACTER SET utf8 NOT NULL,
      `store_id` int(11) NOT NULL,
      `quote_id` int(11) NOT NULL,
      PRIMARY KEY (`guest_email_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;
    
    ");
} catch (Exception $e) {
    //
}

$installer->endSetup();