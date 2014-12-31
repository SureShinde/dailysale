<?php
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('newsletterbooster/subscriber'),
    'imported',
    'TINYINT(1)  NOT NULL default 0'
);

$installer->endSetup();