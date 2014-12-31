<?php

class TM_NewsletterBooster_Block_Adminhtml_Queue_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('tmQueueGrid');
        $this->setDefaultSort('start_at');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('newsletterbooster/queue_collection');
        $collection->setOrder('queue_id', 'DESC');
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('queue_id', array(
            'header'    =>  Mage::helper('newsletterbooster')->__('ID'),
            'index'     =>	'queue_id',
            'type'      => 'number'
        ));

        $this->addColumn('queue_title', array(
            'header'    =>  Mage::helper('newsletterbooster')->__('Queue Title'),
            'index'     =>  'queue_title',
        ));

        $this->addColumn('template_code', array(
            'header'    =>  Mage::helper('newsletterbooster')->__('Campaign'),
            'index'     =>  'template_code',
        ));

        $this->addColumn('start_at', array(
            'header'    =>  Mage::helper('newsletterbooster')->__('Queue Start'),
            'type'      =>	'datetime',
            'index'     =>	'queue_start_at',
            'gmtoffset' => true,
            'default'	=> 	' ---- '
        ));

        $this->addColumn('finish_at', array(
            'header'    =>  Mage::helper('newsletterbooster')->__('Queue Finish'),
            'type'      => 	'datetime',
            'index'     =>	'queue_finish_at',
            'gmtoffset' => true,
            'default'	=> 	' ---- '
        ));

        $this->addColumn('customer_sent', array(
            'header'    =>  Mage::helper('newsletterbooster')->__('Success'),
            'type'      => 'number',
            'index'     => 'customer_sent',
            'filter'    =>  false,
            'sortable'  =>  false
        ));

        $this->addColumn('errors', array(
            'header'    =>  Mage::helper('newsletterbooster')->__('Errors'),
            'type'      => 'number',
            'index'     => 'errors',
            'filter'    =>  false,
            'sortable'  =>  false
        ));

        $this->addColumn('recipients', array(
            'header'    =>  Mage::helper('newsletterbooster')->__('Recipients'),
            'type'      => 'number',
            'index'     => 'recipients',
            'filter'    =>  false,
            'sortable'  =>  false
        ));

         $this->addColumn('status', array(
            'header'    => Mage::helper('newsletterbooster')->__('Status'),
            'index'	=> 'queue_status',
            'type'      => 'options',
            'options'   => array(
                TM_NewsletterBooster_Model_Queue::STATUS_SENT 	=> Mage::helper('newsletterbooster')->__('Sent'),
                TM_NewsletterBooster_Model_Queue::STATUS_CANCEL	=> Mage::helper('newsletterbooster')->__('Cancelled'),
                TM_NewsletterBooster_Model_Queue::STATUS_NEVER 	=> Mage::helper('newsletterbooster')->__('Not Sent'),
                TM_NewsletterBooster_Model_Queue::STATUS_SENDING => Mage::helper('newsletterbooster')->__('Sending'),
                TM_NewsletterBooster_Model_Queue::STATUS_PAUSE 	=> Mage::helper('newsletterbooster')->__('Paused'),
            ),
            'width'     => '100px',
        ));

        $this->addColumn('action', array(
            'header'    =>  Mage::helper('newsletterbooster')->__('Action'),
            'filter'	=>	false,
            'sortable'	=>	false,
            'no_link'   => true,
            'width'		=> '100px',
            'renderer'	=>	'newsletterbooster/adminhtml_queue_grid_renderer_action'
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id'=>$row->getId()));
    }

}

