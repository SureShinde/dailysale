<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Relatedproducts
 * @version    1.4.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


$installer = $this;
$installer->startSetup();

$installer->run("
DELETE FROM {$this->getTable('relatedproducts/relatedproducts')};
ALTER TABLE {$this->getTable('relatedproducts/relatedproducts')} ADD COLUMN `store_id` SMALLINT(5) UNSIGNED DEFAULT '0' NOT NULL AFTER `product_id`;
ALTER TABLE {$this->getTable('relatedproducts/relatedproducts')} ADD KEY `FK_WBTAB_INT_STORE_ID` (`store_id`);
ALTER TABLE {$this->getTable('relatedproducts/relatedproducts')} ADD CONSTRAINT `FK_WBTAB_INT_STORE_ID` FOREIGN KEY (`store_id`) REFERENCES `{$this->getTable('core_store')}` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;
");
$installer->endSetup();