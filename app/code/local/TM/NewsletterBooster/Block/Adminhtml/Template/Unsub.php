<?php

class TM_NewsletterBooster_Block_Adminhtml_Template_Unsub extends Mage_Adminhtml_Block_Widget_Grid
{

    protected function _construct()
    {
        $this->setEmptyText(Mage::helper('newsletterbooster')->__('No Unsubscribers Found'));
        $this->setId('newsletterboosterEmailTemplateUnsub');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('newsletterbooster/unsubscribe')->getCollection();
        $campaignId = $this->getRequest()->getParam('campaign_id');
        $collection->getSelect()->where('campaign_id = ?', $campaignId);
        $this->setCollection($collection);
        
        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'    => Mage::helper('newsletterbooster')->__('ID'),
            'align'     =>'right',
            'width'     => '20px',
            'index'     => 'id',
            'type'      => 'number'
        ));

        $this->addColumn('firstname', array(
            'header'    => Mage::helper('newsletterbooster')->__('First Name'),
            'align'     =>'left',
            'width'     => '300px',
            'index'     => 'firstname'
        ));
        
        $this->addColumn('lastname', array(
            'header'    => Mage::helper('newsletterbooster')->__('Last Name'),
            'align'     =>'left',
            'width'     => '300px',
            'index'     => 'lastname'
        ));
        
        $this->addColumn('email', array(
            'header'    => Mage::helper('newsletterbooster')->__('Email'),
            'align'     =>'left',
            'width'     => '300px',
            'index'     => 'email'
        ));
        
        $this->addColumn('is_guest', array(
            'header'    => Mage::helper('newsletterbooster')->__('Guest Unsubscriber'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'is_guest',
            'type'      => 'options',
            'options'   => array(
                1 => Mage::helper('newsletterbooster')->__('Yes'),
                0 => Mage::helper('newsletterbooster')->__('No')
            )
        ));
        
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/unsubscribe', array('_current'=>true));
    }

    // public function getRowUrl($row)
    // {
        // return $this->getUrl('*/*/edit', array('id' => $row->getCampaignId()));
    // }

}
