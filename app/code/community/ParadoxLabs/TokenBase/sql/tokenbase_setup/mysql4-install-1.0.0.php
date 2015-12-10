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
 * @package		TokenBase
 * @author		Ryan Hoerr <magento@paradoxlabs.com>
 * @license		http://store.paradoxlabs.com/license.html
 */

$this->startSetup();

/**
 * Add card table
 */

$table = $this->getTable('tokenbase/card');

$this->run("CREATE TABLE IF NOT EXISTS {$table} (
	id int auto_increment primary key,
	customer_id int,
	customer_email varchar(255),
	customer_ip varchar(32),
	profile_id int,
	payment_id int,
	method varchar(10),
	active tinyint(1) default '1',
	created_at datetime,
	updated_at datetime,
	last_use datetime,
	expires datetime,
	address mediumtext,
	additional mediumtext
);");

/**
 * Add payment columns
 */
$this->getConnection()->addColumn(
		$this->getTable('sales/quote_payment'),
		'tokenbase_id',
		array(
			'type'		=> Varien_Db_Ddl_Table::TYPE_INTEGER,
			'length'	=> 11,
			'unsigned'	=> true,
			'nullable'	=> true,
			'comment'	=> 'ParadoxLabs_TokenBase Card ID'
		)
	);
	
$this->getConnection()->addColumn(
		$this->getTable('sales/order_payment'),
		'tokenbase_id',
		array(
			'type'		=> Varien_Db_Ddl_Table::TYPE_INTEGER,
			'length'	=> 11,
			'unsigned'	=> true,
			'nullable'	=> true,
			'comment'	=> 'ParadoxLabs_TokenBase Card ID'
		)
	);

$this->endSetup();
