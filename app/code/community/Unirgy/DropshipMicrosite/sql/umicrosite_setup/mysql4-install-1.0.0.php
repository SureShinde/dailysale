<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

$this->startSetup();

$conn = $this->_conn;

$vt = $this->getTable('udropship_vendor');

/*
$t = $this->getTable('cms_page');
$conn->addColumn($t, 'udropship_vendor', 'int(11) unsigned');
$conn->addConstraint('FK_CMS_PAGE_VENDOR', $t, 'udropship_vendor', $vt, 'vendor_id');

$t = $this->getTable('cms_block');
$conn->addColumn($t, 'udropship_vendor', 'int(11) unsigned');
$conn->addConstraint('FK_CMS_BLOCK_VENDOR', $t, 'udropship_vendor', $vt, 'vendor_id');
*/
$t = $this->getTable('admin_user');
$conn->modifyColumn($t, 'username', 'varchar(128) NOT NULL DEFAULT \'\'');
$conn->addColumn($t, 'udropship_vendor', 'int(11) unsigned');
$conn->addConstraint('FK_ADMIN_USER_VENDOR', $t, 'udropship_vendor', $vt, 'vendor_id');

$t = $this->getTable('admin_role');
$roleId = $conn->fetchOne("select role_id from {$t} where role_name='Dropship Vendor'");
if (!$roleId) {
    $conn->insert($t, array('tree_level'=>1, 'role_type'=>'G', 'role_name'=>'Dropship Vendor'));
    $roleId = $conn->lastInsertId($t);

    $rules = new Mage_Admin_Model_Rules();
    $rules->setResources(array(/*'admin/cms', 'admin/cms/page', */'admin/catalog', 'admin/catalog/products'));
    $rules->setRoleId($roleId)->saveRel();
}

$ut = $this->getTable('admin_user');
$vendors = $conn->fetchAll("select * from {$this->getTable('udropship_vendor')}");
$coreHlp = new Mage_Core_Helper_Data();
foreach ($vendors as $v) {
    if ($conn->fetchOne("select user_id from {$ut} where username=?", $v['email'])) {
        continue;
    }
    $conn->insert($ut, array(
        'firstname' => $v['vendor_name'],
        'lastname'  => $v['vendor_attn'],
        'email'     => $v['email'],
        'username'  => $v['email'],
        'password'  => $coreHlp->getHash($v['password'], 2),
        'created'   => now(),
        'is_active' => 1,
        'udropship_vendor' => $v['vendor_id'],
    ));
    $userId = $conn->lastInsertId($ut);
    $conn->insert($t, array(
        'parent_id'=>$roleId,
        'tree_level'=>2,
        'role_type'=>'U',
        'user_id'=>$userId,
        'role_name'=>$v['vendor_name'],
    ));
}

$this->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('udropship_vendor_registration')}`  (
`reg_id` int(10) unsigned NOT NULL auto_increment,
`store_id` smallint(5) unsigned NOT NULL,
`vendor_name` varchar(255) default NULL,
`telephone` varchar(255) default NULL,
`email` varchar(255) default NULL,
`password_enc` varchar(255) default NULL,
`password_hash` varchar(255) default NULL,
`carrier_code` varchar(64) default NULL,
`vendor_attn` varchar(255) default NULL,
`street` text,
`city` varchar(255) default NULL,
`zip` varchar(255) default NULL,
`region_id` int(10) unsigned default NULL,
`region` varchar(255) default NULL,
`country_id` char(2) default NULL,
`remote_ip` varchar(15) default NULL,
`registered_at` datetime default NULL,
`url_key` varchar(64) default NULL,
`comments` text,
`notes` text,
PRIMARY KEY  (`reg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$this->endSetup();