<?php

$installer = $this;

$installer->startSetup();

try {
    $installer->dropTable('recommendation');
    $installer->createTable('recommendation');
} catch (Exception $e) {
    Mage::helper('bronto_product')->writeError('Failed to create Product Recommentation table: ' . $e->getMessage());
}

$installer->endSetup();
