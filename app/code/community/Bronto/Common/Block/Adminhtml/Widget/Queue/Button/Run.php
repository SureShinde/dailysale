<?php

class Bronto_Common_Block_Adminhtml_Widget_Queue_Button_Run extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * @see parent
     */
    protected function _construct()
    {
        $this->setLabel('Run Now');
        $this->setOnClick("setLocation('" . $this->getRunUrl() . "'); return false;");
        $this->setClass('bronto-cron-run');

        if (!Mage::helper('bronto_common/api')->canUseQueue()) {
            $this->setDisabled(true)->setClass('disabled');
        }
    }

    /**
     * Get the run url for the API send queue
     *
     * @return string
     */
    public function getRunUrl()
    {
        return $this->getUrl('*/debug/send');
    }
}
