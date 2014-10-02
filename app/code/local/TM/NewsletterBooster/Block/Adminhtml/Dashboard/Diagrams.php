<?php

class TM_NewsletterBooster_Block_Adminhtml_Dashboard_Diagrams extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('chart_tab');
        $this->setDestElementId('diagram_tab_content');
        $this->setTemplate('widget/tabshoriz.phtml');
    }

    protected function _prepareLayout()
    {
        $this->addTab('track', array(
            'label'     => Mage::helper('newsletterbooster')->__('Statistic'),
            'content'   => $this->getLayout()->createBlock('newsletterbooster/adminhtml_dashboard_tab_track')->toHtml(),
            'active'    => true
        ));

        $this->addTab('geo', array(
            'label'     => Mage::helper('newsletterbooster')->__('Geolocation'),
            'content'   => $this->getLayout()->createBlock('newsletterbooster/adminhtml_dashboard_tab_geo')->toHtml(),
        ));
        return parent::_prepareLayout();
    }
}
