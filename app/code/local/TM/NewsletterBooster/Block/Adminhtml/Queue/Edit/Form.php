<?php

class TM_NewsletterBooster_Block_Adminhtml_Queue_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $saveUrl = $this->getUrl('*/newsletterbooster_queue/save'); 
        $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $saveUrl, 'method' => 'post'));
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

}