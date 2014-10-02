<?php

class TM_NewsletterBooster_Block_Adminhtml_Campaign extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_campaign';
        $this->_blockGroup = 'newsletterbooster';
        $this->_headerText = Mage::helper('newsletterbooster')->__('Manage Campaigns');
        $this->_addButtonLabel = Mage::helper('newsletterbooster')->__('Create Campaign');

        parent::__construct();
    }
}