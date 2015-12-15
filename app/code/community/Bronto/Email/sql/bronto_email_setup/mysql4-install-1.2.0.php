<?php

$installer = $this;

$installer->startSetup();

try {
    $installer->createTables();
} catch (Exception $e) {
    Mage::helper('bronto_email')->writeError($e->getMessage());
}

$installer->endSetup();
