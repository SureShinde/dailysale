<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipBatch_Block_Adminhtml_Vendor_Batch_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('udbatch_vendor_batches');
        $this->setDefaultSort('batch_id');
        $this->setUseAjax(true);
    }

    public function getVendor()
    {
        $batch = Mage::registry('vendor_data');
        if (!$batch) {
            $batch = Mage::getModel('udropship/vendor')->load($this->getBatchId());
            Mage::register('vendor_data', $batch);
        }
        return $batch;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('udbatch/batch')->getCollection()
            ->addFieldToFilter('vendor_id', $this->getVendor()->getId());
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('batch_id', array(
            'header'    => Mage::helper('udropship')->__('ID'),
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

        $this->addColumn('batch_status', array(
            'header' => Mage::helper('udropship')->__('Batch Status'),
            'index' => 'batch_status',
            'type' => 'options',
            'options' => Mage::getSingleton('udbatch/source')->setPath('batch_status')->toOptionHash(),
        ));

        $this->addColumn('num_rows', array(
            'header'    => Mage::helper('udropship')->__('# of Rows'),
            'index'     => 'num_rows',
            'type'      => 'number',
        ));

        $this->addColumn('scheduled_at', array(
            'header'    => Mage::helper('udropship')->__('Scheduled At'),
            'index'     => 'scheduled_at',
            'type'      => 'datetime',
            'width'     => 150,
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('udbatchadmin/adminhtml_batch/edit', array('id' => $row->getId()));
    }

    public function getGridUrl()
    {
        return $this->getUrl('udbatchadmin/adminhtml_batch/vendorBatchesGrid', array('_current'=>true));
    }
}
