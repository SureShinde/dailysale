<?php

class TM_NewsletterBooster_Block_Adminhtml_Dashboard_Last_Campaigns extends Mage_Adminhtml_Block_Dashboard_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('lastCampaignsGrid');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('newsletterbooster/campaign_collection');

        $collection->setOrder('campaign_id', 'DESC');
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepares page sizes for dashboard grid with las 5 orders
     *
     * @return void
     */
    protected function _preparePage()
    {
        $this->getCollection()->setPageSize($this->getParam($this->getVarNameLimit(), $this->_defaultLimit));
        // Remove count of total orders $this->getCollection()->setCurPage($this->getParam($this->getVarNamePage(), $this->_defaultPage));
    }

    protected function _prepareColumns()
    {
        $this->addColumn('template_code', array(
            'header'    => Mage::helper('newsletterbooster')->__('Campaign'),
            'sortable'  => false,
            'index'     => 'template_code',
        ));

        $this->addColumn('queue_count', array(
            'header'    => Mage::helper('newsletterbooster')->__('Sended NewsLetter'),
            'align'     => 'right',
            'type'      => 'number',
            'sortable'  => false,
            'index'     => 'queue_count'
        ));

        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/newsletterbooster_campaign/edit', array('id'=>$row->getCampaignId()));
    }
}
