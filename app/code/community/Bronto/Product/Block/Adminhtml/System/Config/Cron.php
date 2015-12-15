<?php

class Bronto_Product_Block_Adminhtml_System_Config_Cron extends Bronto_Common_Block_Adminhtml_System_Config_Cron
{
    protected $_jobCode = 'bronto_product_parse_tag';

    /**
     * @see parent
     */
    protected function _prepareLayout()
    {
        $this->addButton($this->getLayout()->createBlock('bronto_product/adminhtml_widget_button_run'));
        return parent::_prepareLayout();
    }

    /**
     * Determine if the cron table should show
     *
     * @return bool
     */
    public function showCronTable()
    {
        return Mage::helper('bronto_product')->canUseMageCron();
    }
}
