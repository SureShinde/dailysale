<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Promo_Notice extends Mage_Adminhtml_Block_Template
{
    /**
     * Preparing block layout
     *
     * @return Bronto_Reminder_Block_Adminhtml_Promo_Notice
     */
    protected function _prepareLayout()
    {
        if ($salesRule = Mage::registry('current_promo_quote_rule')) {
            $resource = Mage::getResourceModel('bronto_reminder/rule');
            if ($count = $resource->getAssignedRulesCount($salesRule->getId())) {
                $confirm = Mage::helper('bronto_reminder')->__('This rule is assigned to %s automated reminder rule(s). Deleting this rule will automatically unassign it.', $count);
                $block   = $this->getLayout()->getBlock('promo_quote_edit');
                if ($block instanceof Mage_Adminhtml_Block_Promo_Quote_Edit) {
                    $block->updateButton('delete', 'onclick', 'deleteConfirm(\'' . $confirm . '\', \'' . $block->getDeleteUrl() . '\')');
                }
            }
        }

        return $this;
    }
}
