<?php

$installer = $this;

$installer->startSetup();

try {
    $installer->setCustomEmail();
    $installer->createTable('log');
    $installer->createTable('post_purchase');
} catch (Exception $e) {
    Mage::helper('bronto_reviews')->writeError('Failed to update post purchase to 0.0.2:' . $e->getMessage());
}

$installer->endSetup();
