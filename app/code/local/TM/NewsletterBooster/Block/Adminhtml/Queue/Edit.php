<?php

class TM_NewsletterBooster_Block_Adminhtml_Queue_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'queue_id';
        $this->_controller = 'adminhtml_queue';
        $this->_blockGroup = 'newsletterbooster';
    
        parent::__construct();
        $this->_updateButton('save', 'label', Mage::helper('newsletterbooster')->__('Save Queue'));
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save and Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('tm_current_queue')) {
            $title = Mage::registry('tm_current_queue')->getQueueTitle();
            $headText = Mage::helper('newsletterbooster')->__('Edit Queue - %s',$title);
        } else {
            $headText = Mage::helper('newsletterbooster')->__('New Queue');
        }
        return $headText;
    }
}
