<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->changeColumn($installer->getTable('bestsellercron/fiuze_tasks'), 'step_timestamp', 'step_timestamp', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable' => true,

    ));

$installer->endSetup();