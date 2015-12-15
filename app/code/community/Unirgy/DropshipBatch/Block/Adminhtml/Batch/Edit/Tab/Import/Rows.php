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

class Unirgy_DropshipBatch_Block_Adminhtml_Batch_Edit_Tab_Import_Rows extends Mage_Adminhtml_Block_Widget_Grid
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
            $batch = Mage::getModel('udbatch/batch')->load($this->getBatchId());
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
            'header'    => Mage::helper('udropship')->__('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'row_id'
        ));
        if (in_array($this->getBatch()->getBatchType(), array('import_inventory', 'export_inventory'))) {
            $this->addColumn('sku', array(
	            'header'    => Mage::helper('udropship')->__('Sku'),
	            'index'     => 'sku'
	        ));
	        $this->addColumn('vendor_cost', array(
	            'header'    => Mage::helper('udropship')->__('Cost'),
	            'index'     => 'vendor_cost'
	        ));
	        $this->addColumn('stock_qty', array(
	            'header'    => Mage::helper('udropship')->__('Stock Qty'),
	            'index'     => 'stock_qty'
	        ));
            $this->addColumn('stock_qty_add', array(
	            'header'    => Mage::helper('udropship')->__('Stock Qty Add'),
	            'index'     => 'stock_qty_add'
	        ));
	        $this->addColumn('vendor_sku', array(
	            'header'    => Mage::helper('udropship')->__('Vendor Sku'),
	            'index'     => 'vendor_sku'
	        ));
            if (Mage::helper('udropship')->isUdmultiAvailable()) {
                $this->addColumn('status', array(
                    'header'    => Mage::helper('udropship')->__('Status'),
                    'index'     => 'status',
                    'type'      => 'options',
                    'options'   => Mage::getSingleton('udmulti/source')->setPath('vendor_product_status')->toOptionHash(),
                ));
                /*
                $this->addColumn('avail_state', array(
                    'header'    => Mage::helper('udropship')->__('Availability State'),
                    'index'     => 'avail_state',
                    'type'      => 'options',
                    'options'   => Mage::getSingleton('udmulti/source')->setPath('avail_state')->toOptionHash(),
                ));
                $this->addColumn('avail_date', array(
                    'header'    => Mage::helper('udropship')->__('Availability Date'),
                    'index'     => 'avail_date',
                    'type'      => 'date',
                ));
                */
            } else {
                $this->addColumn('stock_status', array(
                    'header'    => Mage::helper('udropship')->__('Stock Status'),
                    'index'     => 'stock_status',
                    'type'      => 'options',
                    'options'   => Mage::getSingleton('udbatch/source')->setPath('stock_status')->toOptionHash(),
                ));
            }
            if (Mage::helper('udropship')->isUdmultiPriceAvailable()) {
                $this->addColumn('state', array(
                    'header'    => Mage::helper('udropship')->__('State'),
                    'index'     => 'state',
                    'type'      => 'options',
                    'options'   => Mage::getSingleton('udmultiprice/source')->setPath('vendor_product_state')->toOptionHash(),
                ));
                $this->addColumn('vendor_price', array(
                    'header'    => Mage::helper('udropship')->__('Vendor Price'),
                    'index'     => 'vendor_price'
                ));
                $this->addColumn('special_price', array(
                    'header'    => Mage::helper('udropship')->__('Special Price'),
                    'index'     => 'special_price'
                ));
                $this->addColumn('special_from_date', array(
                    'header'    => Mage::helper('udropship')->__('Special From'),
                    'type'      => 'date',
                    'index'     => 'special_from_date'
                ));
                $this->addColumn('special_to_date', array(
                    'header'    => Mage::helper('udropship')->__('Special To'),
                    'type'      => 'date',
                    'index'     => 'special_to_date'
                ));
            }
        } else {
	        $this->addColumn('order_increment_id', array(
	            'header'    => Mage::helper('udropship')->__('Order ID'),
	            'index'     => 'order_increment_id'
	        ));
	        $this->addColumn('po_increment_id', array(
	            'header'    => Mage::helper('udropship')->__('PO ID'),
	            'index'     => 'po_increment_id'
	        ));
	        $this->addColumn('tracking_id', array(
	            'header'    => Mage::helper('udropship')->__('Tracking ID'),
	            'index'     => 'tracking_id'
	        ));
        }
        $this->addColumn('has_error', array(
            'header'    => Mage::helper('udropship')->__('Has error'),
            'index'     => 'has_error',
            'type'      => 'options',
            'options'   => Mage::getSingleton('udropship/source')->setPath('yesno')->toOptionHash(),
        ));
        $this->addColumn('error_info', array(
            'header'    => Mage::helper('udropship')->__('Error Info'),
            'index'     => 'error_info',
        ));
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/rowGrid', array('_current'=>true, 'id'=>Mage::registry('batch_data')->getId(), 'type'=>'import_orders'));
    }
}
