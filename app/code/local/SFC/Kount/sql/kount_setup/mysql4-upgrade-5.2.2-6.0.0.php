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

// Custom order statuses?
// Kount Review
$sStatus = SFC_Kount_Helper_Order::ORDER_STATUS_KOUNT_REVIEW;
$sStatusLabel = SFC_Kount_Helper_Order::ORDER_STATUS_KOUNT_REVIEW_LABEL;
$oInstaller->run("
UPDATE
  `{$this->getTable('sales_order_status')}`
SET
  label = '{$sStatusLabel}'
WHERE
  status = '{$sStatus}'
;");
// Kount Decline
$sStatus = SFC_Kount_Helper_Order::ORDER_STATUS_KOUNT_DECLINE;
$sStatusLabel = SFC_Kount_Helper_Order::ORDER_STATUS_KOUNT_DECLINE_LABEL;
$oInstaller->run("
UPDATE
  `{$this->getTable('sales_order_status')}`
SET
  label = '{$sStatusLabel}'
WHERE
  status = '{$sStatus}'
;");
