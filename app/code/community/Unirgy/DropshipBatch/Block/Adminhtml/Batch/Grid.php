<?php

class Unirgy_DropshipBatch_Block_Adminhtml_Batch_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('batchGrid');
        $this->setDefaultSort('batch_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('batch_filter');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('udbatch/batch')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $baseUrl = $this->getUrl();

        $this->addColumn('batch_id', array(
            'header'    => Mage::helper('udropship')->__('ID'),
            'index'     => 'batch_id',
            'width'     => 10,
            'type'      => 'number',

        ));

        $this->addColumn('vendor_id', array(
            'header' => Mage::helper('udropship')->__('Vendor'),
            'index' => 'vendor_id',
            'type' => 'options',
            'options' => Mage::getSingleton('udropship/source')->setPath('vendors')->toOptionHash(),
            'filter' => 'udropship/vendor_gridColumnFilter'
        ));

        $this->addColumn('batch_type', array(
            'header' => Mage::helper('udropship')->__('Batch Type'),
            'index' => 'batch_type',
            'type' => 'options',
            'options' => Mage::getSingleton('udbatch/source')->setPath('batch_type')->toOptionHash(),
        ));

        $this->addColumn('batch_status', array(
            'header' => Mage::helper('udropship')->__('Batch Status'),
            'index' => 'batch_status',
            'type' => 'options',
            'options' => Mage::getSingleton('udbatch/source')->setPath('batch_status')->toOptionHash(),
            'renderer'  => 'udbatch/adminhtml_dist_grid_status',
        ));

        $this->addColumn('num_rows', array(
            'header'    => Mage::helper('udropship')->__('# of Rows'),
            'index'     => 'num_rows',
            'type'      => 'number',
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('udropship')->__('Created At'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'width'     => 150,
        ));

        $this->addColumn('updated_at', array(
            'header'    => Mage::helper('udropship')->__('Updated At'),
            'index'     => 'updated_at',
            'type'      => 'datetime',
            'width'     => 150,
        ));

        $this->addColumn('scheduled_at', array(
            'header'    => Mage::helper('udropship')->__('Scheduled At'),
            'index'     => 'scheduled_at',
            'type'      => 'datetime',
            'width'     => 150,
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('adminhtml')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('adminhtml')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('batch_id');
        $this->getMassactionBlock()->setFormFieldName('batch');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'=> Mage::helper('udropship')->__('Delete'),
             'url'  => $this->getUrl('*/*/massDelete'),
             'confirm' => Mage::helper('udropship')->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
