<?php

class TM_SegmentationSuite_Block_Adminhtml_Segments_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('segmentsGrid');
        $this->setDefaultSort('segment_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        $this->setVarNameFilter('segment_rule_filter');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('segmentationsuite/segments')->getCollection();

        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('segment_id', array(
            'header'    => Mage::helper('segmentationsuite')->__('ID'),
            'align'     =>'right',
            'width'     => '20px',
            'index'     => 'segment_id',
            'type'      => 'number'
        ));

        $this->addColumn('segment_title', array(
            'header'    => Mage::helper('segmentationsuite')->__('Title'),
            'align'     =>'left',
            'width'     => '550px',
            'index'     => 'segment_title'
        ));

        /* $this->addColumn('store_id', array(
            'header'        => Mage::helper('prolabels')->__('Store View'),
            'index'         => 'store_id',
            'type'          => 'store',
            'store_all'     => true,
            'store_view'    => true,
            'sortable'      => false,
            'filter_condition_callback'
                            => array($this, '_filterStoreCondition'),
        )); */

        $this->addColumn('segment_status', array(
            'header'    => Mage::helper('segmentationsuite')->__('Status'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'segment_status',
            'type'      => 'options',
            'options'   => array(
                1 => 'Enabled',
                0 => 'Disabled'
            )
        ));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getSegmentId()));
    }
}