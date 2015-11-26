<?php

$installer = $this;

$installer->startSetup();

try {
    $installer->updateTables('1.2.2');
} catch (Exception $e) {
    Mage::helper('bronto_email')->writeError('Failed to upgrade email to 1.2.2: ' . $e->getMessage());
}

$installer->endSetup();
