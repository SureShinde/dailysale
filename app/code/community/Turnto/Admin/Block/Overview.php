<?php

class Turnto_Admin_Block_Overview extends Mage_Adminhtml_Block_Template
{
	
    public function __construct()
    {   	
        parent::__construct();
        $this->setTemplate('turnto/overview.phtml');       
    } 
}