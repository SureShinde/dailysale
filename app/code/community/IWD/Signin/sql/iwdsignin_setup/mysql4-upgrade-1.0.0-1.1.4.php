<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("
CREATE TABLE `{$installer->getTable('signin/related')}` (
`entity_id` int(11) NOT NULL AUTO_INCREMENT,
`customer_id` int(10) unsigned NOT NULL,
`social` varchar(45) NOT NULL,
`hash` varchar(255) NOT NULL,
PRIMARY KEY (`entity_id`),
KEY `FK_SIGNIN_CUSTOMER_idx` (`customer_id`),
CONSTRAINT `FK_SIGNIN_CUSTOMER` FOREIGN KEY (`customer_id`) REFERENCES `{$installer->getTable('customer_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
");
$installer->endSetup();