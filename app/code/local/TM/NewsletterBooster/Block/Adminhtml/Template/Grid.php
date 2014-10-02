<?php

class TM_NewsletterBooster_Block_Adminhtml_Template_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    protected function _construct()
    {
        $this->setEmptyText(Mage::helper('newsletterbooster')->__('No Campaigns Found'));
        $this->setId('newsletterboosterEmailTemplateGrid');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('newsletterbooster/campaign')->getCollection();
        
        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('campaign_id', array(
            'header'    => Mage::helper('newsletterbooster')->__('ID'),
            'align'     =>'right',
            'width'     => '20px',
            'index'     => 'campaign_id',
            'type'      => 'number'
        ));

        $this->addColumn('template_code', array(
            'header'    => Mage::helper('newsletterbooster')->__('Title'),
            'align'     =>'left',
            'width'     => '550px',
            'index'     => 'template_code'
        ));
        
        $this->addColumn('segment_title', array(
            'header'    => Mage::helper('newsletterbooster')->__('Segment'),
            'align'     =>'left',
            'width'     => '250px',
            'index'     => 'segment_title',
            'filter'    => false,
            'sortable'  => false
        ));
        
        $this->addColumn('gateway_name', array(
            'header'    => Mage::helper('newsletterbooster')->__('Gateway'),
            'align'     =>'left',
            'width'     => '250px',
            'index'     => 'name',
            'filter'    => false,
            'sortable'  => false
        ));
        
        $this->addColumn('track_opens', array(
            'header'    => Mage::helper('segmentationsuite')->__('Track Opens'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'track_opens',
            'type'      => 'options',
            'options'   => array(
                1 => Mage::helper('newsletterbooster')->__('Yes'),
                0 => Mage::helper('newsletterbooster')->__('No')
            )
        ));
        
        $this->addColumn('track_clicks', array(
            'header'    => Mage::helper('segmentationsuite')->__('Track Clicks'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'track_clicks',
            'type'      => 'options',
            'options'   => array(
                1 => Mage::helper('newsletterbooster')->__('Yes'),
                0 => Mage::helper('newsletterbooster')->__('No')
            )
        ));
        
        $this->addColumn('google_analitics', array(
            'header'    => Mage::helper('segmentationsuite')->__('Google Analitics'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'google_analitics',
            'type'      => 'options',
            'options'   => array(
                1 => Mage::helper('newsletterbooster')->__('Yes'),
                0 => Mage::helper('newsletterbooster')->__('No')
            )
        ));
        
        $this->addColumn('google_title', array(
            'header'    => Mage::helper('newsletterbooster')->__('Google Analitics Title'),
            'align'     =>'left',
            'width'     => '250px',
            'index'     => 'google_title'
        ));
        
        $this->addColumn('sub',
            array(
                'header'    => Mage::helper('newsletterbooster')->__('Subscribers'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('newsletterbooster')->__('View'),
                        'url'     => array('base'=>'*/newsletterbooster_campaign/subs'),
                        'field'   => 'campaign_id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));    
        
        $this->addColumn('unsub',
            array(
                'header'    => Mage::helper('newsletterbooster')->__('Unsubscribers'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('newsletterbooster')->__('View'),
                        'url'     => array('base'=>'*/newsletterbooster_campaign/unsub'),
                        'field'   => 'campaign_id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
        
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getCampaignId()));
    }

}

