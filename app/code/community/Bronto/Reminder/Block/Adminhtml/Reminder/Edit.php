<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Reminder_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * @var string
     */
    protected $_objectId = 'id';

    /**
     * @var string
     */
    protected $_controller = 'adminhtml_reminder';

    /**
     * @var string
     */
    protected $_blockGroup = 'bronto_reminder';

    public function __construct()
    {
        parent::__construct();
        $rule = Mage::registry('current_reminder_rule');
        $this->removeButton('reset');
        $this->setValidationUrl($this->getUrl('*/*/validate'));

        if ($rule) {
            $this->_updateButton('save', 'label', Mage::helper('bronto_reminder')->__('Save'));
            $this->_updateButton('delete', 'label', Mage::helper('bronto_reminder')->__('Delete'));

            if ($rule->getId()) {
                $confirm = Mage::helper('bronto_reminder')->__('Are you sure you want to match this rule now?');
                $this->_addButton('match_now', array(
                    'label'   => Mage::helper('bronto_reminder')->__('Match Now'),
                    'onclick' => "confirmSetLocation('{$confirm}', '{$this->getMatchUrl()}')"
                ), -1);
                if ($limit = Mage::helper('bronto_reminder')->getOneRunLimit()) {
                    $confirm .= ' ' . Mage::helper('bronto_reminder')->__('Up to %s customers may receive a reminder email after this action.', $limit);
                }

                $sendButtonSettings = array(
                    'label' => Mage::helper('bronto_reminder')->__('Send Now')
                );

                if (!Mage::helper('bronto_reminder')->isAllowSendForAny()) {
                    $sendButtonSettings['disabled'] = 'disabled';
                } else {
                    $sendButtonSettings['onclick'] = "confirmSetLocation('{$confirm}', '{$this->getRunUrl()}')";
                }

                $this->_addButton('run_now', $sendButtonSettings, -1);
            }

            $this->_addButton('save_and_continue_edit', array(
                'class'   => 'save',
                'label'   => Mage::helper('bronto_reminder')->__('Save and Continue Edit'),
                'onclick' => 'editForm.submit($(\'edit_form\').action + \'back/edit/\')',
            ), 3);
        }
    }

    public function getHeaderText()
    {
        $rule = Mage::registry('current_reminder_rule');
        if ($rule->getRuleId()) {
            return Mage::helper('bronto_reminder')->__("Edit Rule '%s'", $this->htmlEscape($rule->getName()));
        } else {
            return Mage::helper('bronto_reminder')->__('New Rule');
        }
    }

    /**
     * Get url for immediately run sending process
     *
     * @return string
     */
    public function getRunUrl()
    {
        $rule = Mage::registry('current_reminder_rule');

        return $this->getUrl('*/*/run', array('id' => $rule->getRuleId()));
    }

    /**
     * Get url for immediately matching customers
     *
     * @return string
     */
    public function getMatchUrl()
    {
        $rule = Mage::registry('current_reminder_rule');

        return $this->getUrl('*/*/match', array('id' => $rule->getRuleId()));
    }
}
