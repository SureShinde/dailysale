<?php

$installer = $this;

$installer->startSetup();

try {
    $installer->updateTables('1.0.1');
} catch (Exception $e) {
    Mage::helper('bronto_product')->writeError('Failed to update Product Recommentation table: ' . $e->getMessage());
}

$installer->endSetup();
