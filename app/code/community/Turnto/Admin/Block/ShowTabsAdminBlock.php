<?php

class Turnto_Admin_Block_ShowTabsAdminBlock extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('turnto_tab_section_id');       
        $this->setTitle(Mage::helper('adminhelper1')->__('TurnTo'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('turnto_links_tab', array(
            'label'     => Mage::helper('adminhelper1')->__('Overview'),
            'title'     => Mage::helper('adminhelper1')->__('Overview'),
            'content'   => $this->getLayout()->createBlock("adminblock1/Overview")->toHtml(),            
            'active'    => true
        ));

        $this->addTab('turnto_catalog_feed_tab', array(
            'label'     => Mage::helper('adminhelper1')->__('Catalog Feed'),
            'title'     => Mage::helper('adminhelper1')->__('Catalog Feed'),
            'content'   => $this->getLayout()->createBlock("adminblock1/CatalogFeed")->toHtml(),
            'active'    => false
        ));
        
        $this->addTab('turnto_hist_feed_tab', array(
            'label'     => Mage::helper('adminhelper1')->__('Historical Feed'),
            'title'     => Mage::helper('adminhelper1')->__('Historical Feed'),
            'content'   => $this->getLayout()->createBlock("adminblock1/HistoricalFeed")->toHtml(),
            'active'    => false
        ));

        $this->addTab('turnto_ratings_feed_tab', array(
            'label'     => Mage::helper('adminhelper1')->__('Product Ratings Feed'),
            'title'     => Mage::helper('adminhelper1')->__('Product Ratings Feed'),
            'content'   => $this->getLayout()->createBlock("adminblock1/RatingsFeed")->toHtml(),
            'active'    => false
        ));     

		$headBlock = $this->getLayout()->getBlock('head');
		$headBlock->addItem('css', 'calendar/calender-win2k-1.css');
		$headBlock->addItem('js', 'calendar/calender.js');
		$headBlock->addItem('js', 'calendar/calender-setup.js');

        return parent::_beforeToHtml();
    }  
}