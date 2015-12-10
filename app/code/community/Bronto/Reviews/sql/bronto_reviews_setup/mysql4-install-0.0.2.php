<?php

$installer = $this;

$installer->startSetup();

try {
    $installer->createTables();
} catch (Exception $e) {
    Mage::helper('bronto_reviews')->writeError('Failed to create post purchase table: ' . $e->getMessage());
}

$installer->endSetup();
