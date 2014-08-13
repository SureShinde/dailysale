<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Paypal
 * @package     Paypal_Identity
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();


$installer->run("
		CREATE TABLE IF NOT EXISTS `{$installer->getTable('signin/customer')}`(
		`id` INT(10) NOT NULL AUTO_INCREMENT,
		`payer_id` VARCHAR(255) NOT NULL DEFAULT '',
		`customer_id` INT(10) UNSIGNED NOT NULL,
		`email` VARCHAR(255) NOT NULL DEFAULT '',
		PRIMARY KEY (`id`),
		UNIQUE KEY `IDX_UNIQUE_PAYER_ID` (`payer_id`),
		UNIQUE KEY `IDX_UNIQUE_CUSTOMER_ID` (`customer_id`),
		KEY `FK_CUSTOMER_PAYPAL_CUSTOMER_ID` (`customer_id`),
		CONSTRAINT `FK_CUSTOMER_PAYPAL_CUSTOMER_ID` FOREIGN KEY (`customer_id`) REFERENCES `{$installer->getTable('customer_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");



$installer->endSetup();
