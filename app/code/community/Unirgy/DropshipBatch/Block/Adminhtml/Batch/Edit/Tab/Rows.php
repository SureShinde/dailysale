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

class Unirgy_DropshipBatch_Block_Adminhtml_Batch_Edit_Tab_Rows extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('udbatch_batch_rows');
        $this->setDefaultSort('row_id');
        $this->setUseAjax(true);
    }

    public function getBatch()
    {
        $batch = Mage::registry('batch_data');
        if (!$batch) {
            $batch = Mage::getModel('udbatch/vendor')->load($this->getBatchId());
            Mage::register('batch_data', $batch);
        }
        return $batch;
    }

    protected function _prepareCollection()
    {
    	if (in_array($this->getBatch()->getBatchType(), array('import_inventory', 'export_inventory'))) {
            $collection = Mage::getModel('udbatch/batch_invrow')->getCollection()
            	->addFieldToFilter('batch_id', $this->getBatch()->getId());
        } else {
        	$collection = Mage::getModel('udbatch/batch_row')->getCollection()
            	->addFieldToFilter('batch_id', $this->getBatch()->getId());
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('row_id', array(
            'header'    => Mage::helper('udbatch')->__('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'entity_id'
        ));
    	if (in_array($this->getBatch()->getBatchType(), array('import_inventory', 'export_inventory'))) {
            $this->addColumn('sku', array(
	            'header'    => Mage::helper('udbatch')->__('Sku'),
	            'index'     => 'sku'
	        ));
	        $this->addColumn('vendor_cost', array(
	            'header'    => Mage::helper('udbatch')->__('Cost'),
	            'index'     => 'vendor_cost'
	        ));
	        $this->addColumn('stock_qty', array(
	            'header'    => Mage::helper('udbatch')->__('Stock Qty'),
	            'index'     => 'stock_qty'
	        ));
            $this->addColumn('stock_qty_add', array(
	            'header'    => Mage::helper('udbatch')->__('Stock Qty Add'),
	            'index'     => 'stock_qty_add'
	        ));
	        $this->addColumn('vendor_sku', array(
	            'header'    => Mage::helper('udbatch')->__('Vendor Sku'),
	            'index'     => 'vendor_sku'
	        ));
        } else {
        	$this->addColumn('order_increment_id', array(
	            'header'    => Mage::helper('udbatch')->__('Order ID'),
	            'index'     => 'order_increment_id'
	        ));
	        $this->addColumn('po_increment_id', array(
	            'header'    => Mage::helper('udbatch')->__('PO ID'),
	            'index'     => 'po_increment_id'
	        ));
        }
        $this->addColumn('has_error', array(
            'header'    => Mage::helper('udbatch')->__('Has error'),
            'index'     => 'has_error'
        ));
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/rowGrid', array('_current'=>true));
    }
}
