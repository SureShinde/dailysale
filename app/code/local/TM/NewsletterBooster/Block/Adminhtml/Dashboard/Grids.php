<?php

class TM_NewsletterBooster_Block_Adminhtml_Dashboard_Grids extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('grid_tab');
        $this->setDestElementId('grid_tab_content');
        $this->setTemplate('widget/tabshoriz.phtml');
    }
}