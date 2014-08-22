<?php
/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @copyright  Copyright (c) 2012 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Customer Credit extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */

$installer = $this;
 
$installer->startSetup();

$installer->run("
-- DROP TABLE IF EXISTS {$this->getTable('customercredit_rules_customer_action')};
CREATE TABLE IF NOT EXISTS {$this->getTable('customercredit_rules_customer_action')} (
 `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(5) NOT NULL,
  `rule_id` int(5) NOT NULL,
  `action_tag` int(2) NOT NULL,
  `value` int(5) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`,`rule_id`,`action_tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS {$this->getTable('customercredit_rules_customer_log')} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_id` int(11) NOT NULL,
  `action_tag` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rule_id` (`rule_id`,`action_tag`,`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
");
if (!$installer->getConnection()->tableColumnExists($this->getTable('customercredit_code'), 'owner_id')) {
    $installer->run("ALTER TABLE {$this->getTable('customercredit_code')} ADD `owner_id` INT( 11 ) DEFAULT NULL;");   
}
if (!$installer->getConnection()->tableColumnExists($this->getTable('customercredit_rules'), 'rule_type')) {
    $installer->run("ALTER TABLE {$this->getTable('customercredit_rules')} ADD `rule_type` TINYINT( 1 ) NOT NULL , ADD INDEX ( `rule_type` );");   
}
if (!$installer->getConnection()->tableColumnExists($this->getTable('customercredit_credit_log'), 'rule_id')) {
    $installer->run("ALTER TABLE {$this->getTable('customercredit_credit_log')} ADD `rule_id` INT( 5 ) NOT NULL AFTER `rules_customer_id` , ADD INDEX ( `rule_id` ) ;");   
}
$installer->endSetup();