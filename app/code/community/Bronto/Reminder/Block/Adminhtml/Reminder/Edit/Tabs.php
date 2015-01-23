<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Reminder_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    /**
     * Initialize form
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('bronto_reminder_rule_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('bronto_reminder')->__('Email Reminder Rule'));
    }

    /**
     * Add tab sections
     *
     * @return Bronto_Reminder_Block_Adminhtml_Reminder_Edit_Tabs
     */
    protected function _beforeToHtml()
    {
        $this->addTab('general_section', array(
            'label'   => Mage::helper('bronto_reminder')->__('Rule Information'),
            'content' => $this->getLayout()->createBlock('bronto_reminder/adminhtml_reminder_edit_tab_general')->toHtml(),
        ));

        $this->addTab('conditions_section', array(
            'label'   => Mage::helper('bronto_reminder')->__('Conditions'),
            'content' => $this->getLayout()->createBlock('bronto_reminder/adminhtml_reminder_edit_tab_conditions')->toHtml()
        ));

        $this->addTab('bronto_section', array(
            'label'   => Mage::helper('bronto_reminder')->__('Bronto Settings'),
            'content' => $this->getLayout()->createBlock('bronto_reminder/adminhtml_reminder_edit_tab_bronto')->toHtml()
        ));

        $rule = Mage::registry('current_reminder_rule');
        if ($rule && $rule->getId()) {
            $this->addTab('matched_customers', array(
                'label' => Mage::helper('bronto_reminder')->__('Matched Customers'),
                'url'   => $this->getUrl('*/*/customerGrid', array('rule_id' => $rule->getId())),
                'class' => 'ajax'
            ));
        }

        return parent::_beforeToHtml();
    }
}
