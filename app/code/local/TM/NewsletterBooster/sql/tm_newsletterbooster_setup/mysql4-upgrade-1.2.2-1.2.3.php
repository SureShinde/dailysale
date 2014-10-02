<?php
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/campaign'),
    'sent_guest',
    'TINYINT(1)  NOT NULL default 0'
);

$installer->endSetup();