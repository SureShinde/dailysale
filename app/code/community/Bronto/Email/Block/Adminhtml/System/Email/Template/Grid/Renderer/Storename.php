<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Email_Template_Grid_Renderer_Storename extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * @param Varien_Object $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $store     = Mage::getModel('core/store')->load($row->getStoreId());
        $storeName = $store->getName();
        if (!Mage::helper('bronto_email')->isEnabled('store', $store->getId())) {
            $storeName .= ' (Disabled)';
        }

        return $storeName;
    }
}
