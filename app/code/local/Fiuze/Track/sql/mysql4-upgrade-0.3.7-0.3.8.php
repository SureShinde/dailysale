<?php
/**
 * @author Fiuze Team
 * @category Fiuze
 * @package Fiuze_Track
 * @copyright Copyright (c) 2016 Fiuze
 */

$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('track/track'), 'status', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable' => false,
    'comment' => 'Status'
));

$installer->endSetup();
?>