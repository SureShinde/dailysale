<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 * 
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 * 
 * Want to customize or need help with your store?
 *  Phone: 717-431-3330
 *  Email: sales@paradoxlabs.com
 *
 * @category	ParadoxLabs
 * @package		AuthorizeNetCim
 * @author		Ryan Hoerr <magento@paradoxlabs.com>
 * @license		http://store.paradoxlabs.com/license.html
 */

$this->startSetup();

$this->addAttribute('customer', 'authnetcim_profile_id', array(
	'label'				=> 'Authorize.Net CIM: Profile ID',
	'type'				=> 'varchar',
	'input'				=> 'text',
	'default'			=> '',
	'position'			=> 70,
	'visible'			=> true,
	'required'			=> false,
	'user_defined'		=> true,
	'searchable'		=> false,
	'filterable'		=> false,
	'comparable'		=> false,
	'visible_on_front'	=> false,
	'unique'			=> false
));


$table = $this->getTable('authnetcim/card');

$this->run("CREATE TABLE IF NOT EXISTS {$table} (
	id int auto_increment primary key,
	customer_id int,
	profile_id int,
	payment_id int,
	added varchar(255)
);");


$this->endSetup();
