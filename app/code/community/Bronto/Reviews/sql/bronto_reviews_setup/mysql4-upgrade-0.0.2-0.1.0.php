<?php

$installer = $this;
$installer->startSetup();

try {
    $installer->updateTables('0.1.0');
} catch (Exception $e) {
    Mage::helper('bronto_reviews')->writeError('Failed to update post purchase to 0.1.0:' . $e->getMessage());
}

$installer->endSetup();
