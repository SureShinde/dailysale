<?php

class Bronto_News_Block_Adminhtml_System_Config_Releases extends Bronto_News_Block_Adminhtml_System_Config_News
{

    /**
     * @see parent
     */
    protected function _pullRssItems()
    {
        return Mage::getModel('bronto_news/item')->getLimitedReleaseNotes();
    }
}
