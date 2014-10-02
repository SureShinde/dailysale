<?php

class TM_NewsletterBooster_Block_Adminhtml_View_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('newsletterbooster_subscribers_view_grid');
        $this->setDefaultSort('subscriber_id');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('newsletterbooster/subscriber')->getCollection();
        $campaignId = $this->getRequest()->getParam('id');
        $collection->addFieldToFilter('campaign_id', $campaignId);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('subscriber_id', array(
            'header'    => Mage::helper('newsletterbooster')->__('ID'),
            'align'     =>'right',
            'width'     => '20px',
            'index'     => 'subscriber_id',
            'type'      => 'number'
        ));

        $this->addColumn('email', array(
            'header'    => Mage::helper('newsletterbooster')->__('Email'),
            'align'     =>'left',
            'width'     => '350px',
            'index'     => 'email'
        ));

        $this->addColumn('firstname', array(
            'header'    => Mage::helper('newsletterbooster')->__('First name'),
            'align'     =>'left',
            'index'     => 'firstname'
        ));

        $this->addColumn('lastname', array(
            'header'    => Mage::helper('newsletterbooster')->__('Last name'),
            'align'     =>'left',
            'index'     => 'lastname'
        ));

        $this->addColumn('create_at', array(
            'header'    =>  Mage::helper('newsletterbooster')->__('Create at'),
            'type'      =>	'datetime',
            'index'     =>	'create_at',
            'gmtoffset' => true,
            'default'	=> 	' ---- '
        ));

        $this->addColumn('is_guest', array(
            'header'    => Mage::helper('segmentationsuite')->__('Is guest'),
            'align'     => 'left',
            'width'     => '120px',
            'index'     => 'is_guest',
            'type'      => 'options',
            'options'   => array(
                1 => Mage::helper('newsletterbooster')->__('Yes'),
                0 => Mage::helper('newsletterbooster')->__('No')
            )
        ));

        $this->addColumn('imported', array(
            'header'    => Mage::helper('segmentationsuite')->__('Imported'),
            'align'     => 'left',
            'width'     => '120px',
            'index'     => 'imported',
            'type'      => 'options',
            'options'   => array(
                1 => Mage::helper('newsletterbooster')->__('Yes'),
                0 => Mage::helper('newsletterbooster')->__('No')
            )
        ));

        return parent::_prepareColumns();
    }
}