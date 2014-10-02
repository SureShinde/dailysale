<?php
class TM_NewsletterBooster_Block_Adminhtml_Subscriber extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_subscriber';
        $this->_blockGroup = 'newsletterbooster';
        $this->_headerText = Mage::helper('newsletterbooster')->__('Campaigns Subscribers');
        //$this->_addButtonLabel = Mage::helper('newsletterbooster')->__('Create Gateway');

        parent::__construct();
        $this->_removeButton('add');
    }
}