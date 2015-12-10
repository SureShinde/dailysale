<?php

/**
 * @package   Bronto\Customer
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Customer_Block_Adminhtml_System_Config_Suppressed
    extends Bronto_Common_Block_Adminhtml_System_Config_Suppressed
{
    /**
     * Get URL for AJAX call
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return Mage::helper('bronto_common')->getScopeUrl('adminhtml/customer/suppression');
    }

    /**
     * @see parent
     */
    public function getResetUrl()
    {
        return Mage::helper('bronto_common')->getScopeUrl('adminhtml/customer/reset');
    }
}
