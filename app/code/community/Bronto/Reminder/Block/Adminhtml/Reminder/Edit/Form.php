<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Reminder_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('bronto_reminder_rule_form');
        $this->setTitle(Mage::helper('bronto_reminder')->__('Email Reminder Rule'));
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
