<?php

$installer = $this;

$installer->startSetup();
$installer->updateTables('2.3.0');
$installer->resubmitFormInfo();
$installer->endSetup();
