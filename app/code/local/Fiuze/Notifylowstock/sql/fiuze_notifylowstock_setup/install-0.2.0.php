<?php
/* @var $installer Mage_Eav_Model_Entity_Setup */
$installer = $this;
$installer->startSetup();

$installer->addAttribute('catalog_product', 'fiuze_lowstock_flag', array(
    #'position'          => 1,
    #'user_defined'      => 1,
    #'searchable'        => 0,
    #'filterable'        => 0,
    #'comparable'        => 0,
    #'visible_on_front'  => 1,
    #'visible_in_advanced_search' => 0,
    #'is_configurable'   => 0,
    #'unique'            => 0,
    'type'     => 'int',
    'label'    => 'Notify on lowstock qty',
    'input'    => 'select',
    'source'   => 'fiuze_notifylowstock/link',
    'global'   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required' => false,
    'default'  => 0,
    'visible'           => 1,
    #'visible_on_front'  => 1
));
$installer->addAttribute('catalog_product', 'fiuze_lowstock_notif', array(
    #'position'          => 1,
    #'user_defined'      => 1,
    #'searchable'        => 0,
    #'filterable'        => 0,
    #'comparable'        => 0,
    #'visible_on_front'  => 1,
    #'visible_in_advanced_search' => 0,
    #'is_configurable'   => 0,
    #'unique'            => 0,
    'type'     => 'int',
    'label'    => 'It has already been notified (changed automatically)',
    'input'    => 'select',
    'source'   => 'fiuze_notifylowstock/link',
    'global'   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required' => false,
    'default'  => 0,
    'visible'           => 0,
    'visible_on_front'  => 0
));
$installer->addAttribute('catalog_product', 'fiuze_lowstock_qty', array(
    #'position'          => 1,
    #'user_defined'      => 1,
    #'searchable'        => 0,
    #'filterable'        => 0,
    #'comparable'        => 0,
    #'visible_on_front'  => 1,
    #'visible_in_advanced_search' => 0,
    #'is_configurable'   => 0,
    #'unique'            => 0,
    'type'     => 'int',
    'label'    => 'Low stock qty',
    'input'    => 'text',
    #'frontend' => 'adminhtml/system_config_source_yesno',
    'global'   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required' => false,
    'default'  => 0,
    'visible'           => 1,
    # 'visible_on_front'  => 1
));
$installer->endSetup();