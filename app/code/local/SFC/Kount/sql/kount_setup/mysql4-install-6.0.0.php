<?php
// @codingStandardsIgnoreStart
/**
 * StoreFront Consulting Kount Magento Extension
 *
 * PHP version 5
 *
 * @category  SFC
 * @package   SFC_Kount
 * @copyright 2009-2015 StoreFront Consulting, Inc. All Rights Reserved.
 *
 */
// @codingStandardsIgnoreEnd

// Start
$oInstaller = $this;
$oSetup = $oInstaller->startSetup();
$oSetup = new Mage_Sales_Model_Mysql4_Setup('sales_setup');

// Kount Review
$sStatus = SFC_Kount_Helper_Order::ORDER_STATUS_KOUNT_REVIEW;
$sStatusLabel = SFC_Kount_Helper_Order::ORDER_STATUS_KOUNT_REVIEW_LABEL;
$oInstaller->run("
DELETE FROM `{$this->getTable('sales_order_status')}` WHERE status = '{$sStatus}';
INSERT INTO `{$this->getTable('sales_order_status')}` (`status`, `label`) VALUES (
    '{$sStatus}',
    '{$sStatusLabel}'
);
");
$sState = Mage_Sales_Model_Order::STATE_HOLDED;
$oInstaller->run("
DELETE FROM `{$this->getTable('sales_order_status_state')}` WHERE status = '{$sStatus}';
INSERT INTO `{$this->getTable('sales_order_status_state')}` (`status`, `state`, `is_default`) VALUES (
    '{$sStatus}',
    '{$sState}',
    '0'
);
");

// Kount Decline
$sStatus = SFC_Kount_Helper_Order::ORDER_STATUS_KOUNT_DECLINE;
$sStatusLabel = SFC_Kount_Helper_Order::ORDER_STATUS_KOUNT_DECLINE_LABEL;
$oInstaller->run("
DELETE FROM `{$this->getTable('sales_order_status')}` WHERE status = '{$sStatus}';
INSERT INTO `{$this->getTable('sales_order_status')}` (`status`, `label`) VALUES (
    '{$sStatus}',
    '{$sStatusLabel}'
);
");
$sState = Mage_Sales_Model_Order::STATE_HOLDED;
$oInstaller->run("
DELETE FROM `{$this->getTable('sales_order_status_state')}` WHERE status = '{$sStatus}';
INSERT INTO `{$this->getTable('sales_order_status_state')}` (`status`, `state`, `is_default`) VALUES (
    '{$sStatus}',
    '{$sState}',
    '0'
);
");

// Order and quote attributes
// -- Order
if (!$oInstaller->getConnection()->tableColumnExists($oInstaller->getTable('sales_flat_order'), 'kount_ris_score')) {
    $oInstaller->getConnection()->addColumn($oInstaller->getTable('sales_flat_order'), 'kount_ris_score', 'text');
}
if (!$oInstaller->getConnection()->tableColumnExists($oInstaller->getTable('sales_flat_order'), 'kount_ris_response')) {
    $oInstaller->getConnection()->addColumn($oInstaller->getTable('sales_flat_order'), 'kount_ris_response', 'text');
}
if (!$oInstaller->getConnection()->tableColumnExists($oInstaller->getTable('sales_flat_order'), 'kount_ris_rule')) {
    $oInstaller->getConnection()->addColumn($oInstaller->getTable('sales_flat_order'), 'kount_ris_rule', 'text');
}
if (!$oInstaller->getConnection()->tableColumnExists($oInstaller->getTable('sales_flat_order'), 'kount_ris_description')) {
    $oInstaller->getConnection()->addColumn($oInstaller->getTable('sales_flat_order'), 'kount_ris_description', 'text');
}
// -- Quote
if (!$oInstaller->getConnection()->tableColumnExists($oInstaller->getTable('sales_flat_quote'), 'kount_ris_score')) {
    $oInstaller->getConnection()->addColumn($oInstaller->getTable('sales_flat_quote'), 'kount_ris_score', 'text');
}
if (!$oInstaller->getConnection()->tableColumnExists($oInstaller->getTable('sales_flat_quote'), 'kount_ris_response')) {
    $oInstaller->getConnection()->addColumn($oInstaller->getTable('sales_flat_quote'), 'kount_ris_response', 'text');
}
if (!$oInstaller->getConnection()->tableColumnExists($oInstaller->getTable('sales_flat_quote'), 'kount_ris_rule')) {
    $oInstaller->getConnection()->addColumn($oInstaller->getTable('sales_flat_quote'), 'kount_ris_rule', 'text');
}
if (!$oInstaller->getConnection()->tableColumnExists($oInstaller->getTable('sales_flat_quote'), 'kount_ris_description')) {
    $oInstaller->getConnection()->addColumn($oInstaller->getTable('sales_flat_quote'), 'kount_ris_description', 'text');
}

// End
$oInstaller->endSetup();