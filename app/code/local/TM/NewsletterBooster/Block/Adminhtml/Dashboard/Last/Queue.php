<?php

class TM_NewsletterBooster_Block_Adminhtml_Dashboard_Last_Queue extends Mage_Adminhtml_Block_Dashboard_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('lastQueueGrid');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('newsletterbooster/queue_collection');
//        $collectionNew = $collection->addOnlyForSendingFilter()
//            ->addStoresToSelect();
//
//        if($this->getParam('store') && $this->getParam('store') !== 0) {
//            if ($this->getParam('store')) {
//                $collectionNew->addFieldToFilter('store_id', $this->getParam('store'));
//            }
//        }
        $collection->setOrder('queue_id', 'DESC');
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
            'header'    => $this->__('Campaign'),
            'sortable'  => false,
            'index'     => 'template_code',
        ));

        $this->addColumn('queue_title', array(
            'header'    => $this->__('Queue Title'),
            'align'     => 'right',
            'sortable'  => false,
            'index'     => 'queue_title'
        ));

        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/newsletterbooster_queue/edit', array('id'=>$row->getQueueId()));
    }
}
