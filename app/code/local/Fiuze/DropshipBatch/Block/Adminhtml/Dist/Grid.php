<?php
/**
 * DropshipBatch
 *
 * @author      Fiuze Team
 * @category    Fiuze
 * @package     DropshipBatch
 * @copyright   Copyright (c) 2016 Fiuze
 */
class Fiuze_DropshipBatch_Block_Adminhtml_Dist_Grid extends Unirgy_DropshipBatch_Block_Adminhtml_Dist_Grid
{
    /**
     * Override to fix batch id column ambiguous error when sort by batch id
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('dist_id', array(
            'header'    => Mage::helper('udropship')->__('Location ID'),
            'index'     => 'dist_id',
            'width'     => 10,
            'type'      => 'number',
        ));

        $this->addColumn('batch_id', array(
            'header'       => Mage::helper('udropship')->__('Batch ID'),
            'index'        => 'batch_id',
            'width'        => 10,
            'type'         => 'number',
            'filter_index' => 'main_table.batch_id',
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
            'filter_index' => 'b.created_at',
            'type'      => 'datetime',
            'width'     => 150,
        ));

        $this->addColumn('updated_at', array(
            'header'    => Mage::helper('udropship')->__('Hist Updated At'),
            'index'     => 'updated_at',
            'filter_index' => 'main_table.updated_at',
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

        $this->addExportType('*/*/exportCsv', Mage::helper('udropship')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('udropship')->__('XML'));

        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
    }
}
