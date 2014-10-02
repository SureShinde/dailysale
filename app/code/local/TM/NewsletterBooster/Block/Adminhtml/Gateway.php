<?php
class TM_NewsletterBooster_Block_Adminhtml_Gateway extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_gateway';
        $this->_blockGroup = 'newsletterbooster';
        $this->_headerText = Mage::helper('newsletterbooster')->__('Gateway');
        $this->_addButtonLabel = Mage::helper('newsletterbooster')->__('Create Gateway');
        parent::__construct();
    }
}