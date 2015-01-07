<?php

$installer = $this;

$installer->startSetup();

try {
    $installer->updateTables('1.4.18');
} catch (Exception $e) {
    Mage::helper('bronto_reminder')->writeError('Failed to upgrade reminder tables: ' . $e->getMessage());
}

$installer->endSetup();
