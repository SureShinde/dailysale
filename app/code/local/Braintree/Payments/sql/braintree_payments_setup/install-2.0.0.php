<?php
/**
* Braintree Payments Extension
*
* This source file is subject to the Braintree Payment System Agreement (https://www.braintreepayments.com/legal)
*
* DISCLAIMER
* This file will not be supported if it is modified.
*
* @copyright   Copyright (c) 2015 Braintree. (https://www.braintreepayments.com/)
*/

$installer = $this;
$installer->startSetup();
$coreHelper = Mage::helper('core');
$path = 'payment/braintree/private_key';
$readAdapter = Mage::getResourceSingleton('core/resource')->getReadConnection();
$select = $readAdapter->select()
    ->from($installer->getTable('core_config_data'))
    ->where('path like "%' . $path .  '%"')
    ->where('value != ""');

$data = $readAdapter->fetchAll($select);
$isUpdate = count($data) ? true : false;
foreach ($data as $row) {
    $installer->setConfigData(
        $path,
        $coreHelper->encrypt($row['value']),
        $row['scope'],
        $row['scope_id']
    );
}
$helper = Mage::helper('braintree_payments');
if ($isUpdate) {
    $text = 'The Braintree Payments Extension has been successfully upgraded. New fields are ready to be configured.';
} else {
    $text = 'The Braintree Payments Extension has been successfully installed and is ready to be configured.';
}
Mage::getModel('adminnotification/inbox')->addMajor(
    $helper->__($text),
    $helper->__($text),
    '',
    true
);

$installer->endSetup();
