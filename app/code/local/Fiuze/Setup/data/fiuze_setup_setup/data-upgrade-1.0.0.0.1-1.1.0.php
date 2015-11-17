<?php
/**
 * Fiuze Setup
 *
 * @category    Fiuze
 * @package     Setup
 * @copyright   Copyright (c) 2015 Fiuze
 * @author      ahofs
 */
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

/** @var $conn Varien_Db_Adapter_Pdo_Mysql */
$conn = $installer->getConnection();
try {
    $conn->insertMultiple(
        $installer->getTable('admin/permission_block'),
        array(
            array('block_name' => 'catalog/product_list', 'is_allowed' => 1),
        )
    );
} catch (Exception $e) {}
try {
    $conn->insertMultiple(
        $installer->getTable('admin/permission_variable'),
        array(
            array('variable_name' => 'currentVendorLandingPageTitle', 'is_allowed' => 1),
            array('variable_name' => 'currentVendorReviewsSummaryHtml', 'is_allowed' => 1),
            array('variable_name' => 'currentVendor', 'is_allowed' => 1),
        )
    );
} catch (Exception $e) {}

Mage::getConfig()->reinit();
Mage::app()->reinitStores();
Mage::dispatchEvent('adminhtml_cache_flush_all');
Mage::app()->getCacheInstance()->flush();

$installer->endSetup();