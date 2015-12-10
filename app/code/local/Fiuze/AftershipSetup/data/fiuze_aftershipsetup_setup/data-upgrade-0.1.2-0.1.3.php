<?php
$installer = $this;

$installer->startSetup();

$setup = new Mage_Core_Model_Config();

$setup->saveConfig('udropship/customer/notify_on_tracking', '0', 'default', 0);

$installer->endSetup();