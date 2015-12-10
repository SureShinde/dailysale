<?php

class Bronto_News_Block_Adminhtml_System_Config_General extends Bronto_News_Block_Adminhtml_System_Config_News
{

    /**
     * @see parent
     */
    protected function _pullRssItems()
    {
        return Mage::getModel('bronto_news/item')->getLimitedGeneralNotes();
    }
}
