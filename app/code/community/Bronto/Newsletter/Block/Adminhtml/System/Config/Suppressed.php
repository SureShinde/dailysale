<?php

/**
 * @package   Bronto\Newsletter
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Newsletter_Block_Adminhtml_System_Config_Suppressed
    extends Bronto_Common_Block_Adminhtml_System_Config_Suppressed
{
    /**
     * Get URL for AJAX call
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return Mage::helper('bronto_common')->getScopeUrl('adminhtml/newsletter/suppression');
    }

    /**
     * @see parent
     */
    public function getResetUrl()
    {
        return Mage::helper('bronto_common')->getScopeUrl('adminhtml/newsletter/reset');
    }
}
