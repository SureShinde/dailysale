<?php

class Turnto_Admin_Block_RatingsFeed extends Mage_Adminhtml_Block_Template
{
	
    public function __construct()
    {   	
        parent::__construct();
        $this->setTemplate('turnto/product_ratings_feed_tab.phtml');       
    }	 
}