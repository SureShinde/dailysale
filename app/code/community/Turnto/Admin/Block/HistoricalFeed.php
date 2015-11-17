<?php

class Turnto_Admin_Block_HistoricalFeed extends Mage_Adminhtml_Block_Template
{
	
    public function __construct()
    {   	
        parent::__construct();
        $this->setTemplate('turnto/historical_feed_tab.phtml');       
    }	 
}