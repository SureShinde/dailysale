<?php

$installer = $this;

$installer->startSetup();

try {
    $installer->removeListsInDefaultScope();
} catch (Exception $e) {
    Mage::helper('bronto_email')->writeError('Failed to update core data table: ' . $e->getMessage());
}

$installer->endSetup();
