<?php

class Turnto_Admin_Block_CatalogFeed extends Mage_Adminhtml_Block_Template
{
	
    public function __construct()
    {   	
        parent::__construct();
        $this->setTemplate('turnto/catalog_feed_tab.phtml');       
    } 
}