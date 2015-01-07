<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Widget_Grid_Column_Renderer_Email extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render customer email as mailto link
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    protected function _getValue(Varien_Object $row)
    {
        $customerEmail = $this->htmlEscape($row->getData($this->getColumn()->getIndex()));

        return $this->htmlEscape($customerEmail);
    }
}
