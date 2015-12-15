<?php

/**
 * @category   Fiuze
 * @package    Fiuze_Bestsellercron
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
$installer = $this;
$installer->startSetup();


$entityTypeId = Mage::getModel('eav/entity')
    ->setType('catalog_product')
    ->getTypeId();


$installer->addAttribute($entityTypeId, 'bestsellercron_flag', array(
    'type' => 'int',
    'backend' => '',
    'frontend' => '',
    'label' => 'Fiuze Bestsellercron Flag',
    'note' => ' ',
    'input' => 'boolean',
    'class' => '',
    'source' => '',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible' => false,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'searchable' => false,
    'filterable' => false,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'is_configurable' => false
));

$installer->endSetup();
