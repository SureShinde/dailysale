<?php

class Unirgy_DropshipBatch_Block_Adminhtml_Dist_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('distGrid');
        $this->setDefaultSort('dist_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('dist_filter');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('udbatch/batch_dist')->getCollection();

        $res = Mage::getSingleton('core/resource');
        $collection->getSelect()
            ->join(array('b'=>$res->getTableName('udbatch/batch')), 'b.batch_id=main_table.batch_id', array('batch_type', 'vendor_id', 'num_rows', 'batch_created_at'=>'created_at'));

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $baseUrl = $this->getUrl();

        $this->addColumn('dist_id', array(
            'header'    => Mage::helper('udropship')->__('Location ID'),
            'index'     => 'dist_id',
            'width'     => 10,
            'type'      => 'number',
        ));

        $this->addColumn('batch_id', array(
            'header'    => Mage::helper('udropship')->__('Batch ID'),
            'index'     => 'batch_id',
            'width'     => 10,
            'type'      => 'number',
        ));

        $this->addColumn('batch_type', array(
            'header' => Mage::helper('udropship')->__('Batch Type'),
            'index' => 'batch_type',
            'type' => 'options',
            'options' => Mage::getSingleton('udbatch/source')->setPath('batch_type')->toOptionHash(),
        ));

        $this->addColumn('num_rows', array(
            'header'    => Mage::helper('udropship')->__('# of Rows'),
            'index'     => 'num_rows',
            'type'      => 'number',
        ));

        $this->addColumn('vendor_id', array(
            'header' => Mage::helper('udropship')->__('Vendor'),
            'index' => 'vendor_id',
            'type' => 'options',
            'options' => Mage::getSingleton('udropship/source')->setPath('vendors')->toOptionHash(),
            'filter' => 'udropship/vendor_gridColumnFilter'
        ));

        $this->addColumn('location', array(
            'header'    => Mage::helper('udropship')->__('Location'),
            'index'     => 'location',
        ));

        $this->addColumn('batch_created_at', array(
            'header'    => Mage::helper('udropship')->__('Batch Created At'),
            'index'     => 'batch_created_at',
            'type'      => 'datetime',
            'width'     => 150,
        ));

        $this->addColumn('updated_at', array(
            'header'    => Mage::helper('udropship')->__('Hist Updated At'),
            'index'     => 'updated_at',
            'type'      => 'datetime',
            'width'     => 150,
        ));

        $this->addColumn('dist_status', array(
            'header' => Mage::helper('udropship')->__('Status'),
            'index' => 'dist_status',
            'type' => 'options',
            'options' => Mage::getSingleton('udbatch/source')->setPath('dist_status')->toOptionHash(),
            'renderer'  => 'udbatch/adminhtml_dist_grid_status',
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('adminhtml')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('adminhtml')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('dist_id');
        $this->getMassactionBlock()->setFormFieldName('dist');

        $this->getMassactionBlock()->addItem('retry', array(
             'label'=> Mage::helper('udbatch')->__('Retry'),
             'url'  => $this->getUrl('*/*/massRetry'),
             'confirm' => Mage::helper('udropship')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('udropship')->__('Change status'),
             'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
             'additional' => array(
                'status' => array(
                     'name' => 'status',
                     'type' => 'select',
                     'class' => 'required-entry',
                     'label' => Mage::helper('udropship')->__('Status'),
                     'values' => Mage::getSingleton('udbatch/source')->setPath('dist_status')->toOptionArray(true),
                 )
             )
        ));

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
