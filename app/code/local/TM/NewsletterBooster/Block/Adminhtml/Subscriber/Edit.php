<?php

class TM_NewsletterBooster_Block_Adminhtml_Gateway_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'newsletterbooster';
        $this->_controller = 'adminhtml_gateway';
        
        parent::__construct();
        
        $this->_updateButton('save', 'label', Mage::helper('newsletterbooster')->__('Save Gateway'));
        $this->_updateButton('delete', 'label', Mage::helper('newsletterbooster')->__('Delete Gateway'));

        $this->_addButton('test', array(
            'label'     => Mage::helper('newsletterbooster')->__('Save And Test Connection'),
            'onclick'   => "$('edit_form').action = '" . $this->getUrl('*/*/test') . "'; editForm.submit();",
            'class'     => 'save',
        ), -100);

    }

    public function getHeaderText()
    {
        if( Mage::registry('newsletterbooster_gateway')
            && Mage::registry('newsletterbooster_gateway')->getId()) {

            return Mage::helper('newsletterbooster')->__(
                "Edit Email Gateway '%s'",
                $this->htmlEscape(Mage::registry('newsletterbooster_gateway')->getName())
            );
            
        } else {
            return Mage::helper('newsletterbooster')->__('Add New Email Gateway');
        }
    }
}