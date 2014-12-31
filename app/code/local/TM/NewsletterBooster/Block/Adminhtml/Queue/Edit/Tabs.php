<?php

class TM_NewsletterBooster_Block_Adminhtml_Queue_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('newsletterbooster_queue_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('newsletterbooster')->__('Queue Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('queue', array(
            'label'     => Mage::helper('newsletterbooster')->__('Queue Information'),
            'title'     => Mage::helper('newsletterbooster')->__('Queue Information'),
            'content'   => $this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_edit_tab_queue')->toHtml(),
            //'class'     => 'ajax'
        ));
        if ($this->getRequest()->getParam('id')) {
            $this->addTab('tracks', array(
                'label'     => Mage::helper('newsletterbooster')->__('Statistic'),
                'title'     => Mage::helper('newsletterbooster')->__('Statistic'),
                'content'   => $this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_edit_tab_tracks')->toHtml() .
                                $this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_edit_tab_tracktotal')->toHtml(),
            ));

            if (Mage::getStoreConfig('newsletterbooster/geo_ip/enabled')) {
                $this->addTab('geo', array(
                    'label'     => Mage::helper('newsletterbooster')->__('Geolocation'),
                    'title'     => Mage::helper('newsletterbooster')->__('Geolocation'),
                    'content'   => $this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_edit_tab_geo')->toHtml()
                ));
            }

            $this->addTab('customers_opens', array(
                'label'     => Mage::helper('newsletterbooster')->__('Open Customers'),
                'title'     => Mage::helper('newsletterbooster')->__('Open Customers'),
                'content'   => $this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_edit_tab_ocustomers')->toHtml()
            ));

            $this->addTab('customers_clicks', array(
                'label'     => Mage::helper('newsletterbooster')->__('Click Customers'),
                'title'     => Mage::helper('newsletterbooster')->__('Click Customers'),
                'content'   => $this->getLayout()->createBlock('newsletterbooster/adminhtml_queue_edit_tab_ccustomers')->toHtml()
            ));
        }

        return parent::_beforeToHtml();
    }
}
