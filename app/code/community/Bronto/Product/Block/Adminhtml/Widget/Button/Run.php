<?php

class Bronto_Product_Block_Adminhtml_Widget_Button_Run extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * @see parent
     */
    protected function _construct()
    {
        $this->setLabel('Run Now');
        $this->setOnClick("setLocation('" . $this->getRunUrl() . "'); return false;");
        $this->setClass('bronto-cron-run');

        if (!Mage::helper('bronto_product')->isModuleActive()) {
            $this->setDisabled(true)->setClass('disabled');
        }
    }

    /**
     * Gets the run url for product recommendation
     *
     * @return string
     */
    public function getRunUrl()
    {
        return $this->getUrl('*/recommendations/run');
    }
}
