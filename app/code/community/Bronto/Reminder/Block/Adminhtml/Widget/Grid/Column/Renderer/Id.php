<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Widget_Grid_Column_Renderer_Id extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render customer id linked to its account edit page
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    protected function _getValue(Varien_Object $row)
    {
        $customerId = (int)$row->getData($this->getColumn()->getIndex());

        // If We don't have a customer ID, label as Guest
        if (!$customerId) {
            return 'Guest';
        }

        // Create link to edit customer for customer ID
        return '<a href="' . Mage::getSingleton('adminhtml/url')->getUrl('*/customer/edit',
            array('id' => $customerId)) . '">' . $customerId . '</a>';
    }
}
