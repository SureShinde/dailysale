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
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipPo_Block_Adminhtml_Po_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('udpo_po_grid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
    }

    protected function _getCollectionClass()
    {
        return 'udpo/po_grid_collection';
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', array(
            'header'    => Mage::helper('udpo')->__('Purchase Order #'),
            'index'     => 'increment_id',
            'type'      => 'text',
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('udpo')->__('Purchase Order Created'),
            'index'     => 'created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('order_increment_id', array(
            'header'    => Mage::helper('sales')->__('Order #'),
            'index'     => 'order_increment_id',
            'type'      => 'number',
        ));

        $this->addColumn('order_created_at', array(
            'header'    => Mage::helper('sales')->__('Order Date'),
            'index'     => 'order_created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('shipping_name', array(
            'header' => Mage::helper('sales')->__('Ship to Name'),
            'index' => 'shipping_name',
        ));
        
        $this->addColumn('udropship_vendor', array(
            'header' => Mage::helper('udpo')->__('Vendor'),
            'index' => 'udropship_vendor',
            'type' => 'options',
            'options' => Mage::getSingleton('udropship/source')->setPath('vendors')->toOptionHash(),
            'filter' => 'udropship/vendor_gridColumnFilter'
        ));

        if (Mage::helper('udropship')->isModuleActive('ustockpo')) {
            $this->addColumn('ustock_vendor', array(
                'header' => Mage::helper('udpo')->__('Stock Vendor'),
                'index' => 'ustock_vendor',
                'type' => 'options',
                'options' => Mage::getSingleton('udropship/source')->setPath('vendors')->toOptionHash(),
                'filter' => 'udropship/vendor_gridColumnFilter'
            ));
        }
        
        $this->addColumn('udropship_method_description', array(
            'header' => Mage::helper('udropship')->__('Method'),
            'index' => 'udropship_method_description',
        ));
        
        $this->addColumn('base_shipping_amount', array(
            'header' => Mage::helper('sales')->__('Shipping Price'),
            'index' => 'base_shipping_amount',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => Mage::getStoreConfig('currency/options/base'),
        ));

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/udpo_view_cost')) {
        $this->addColumn('total_cost', array(
            'header' => Mage::helper('sales')->__('Total Cost'),
            'index' => 'total_cost',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => Mage::getStoreConfig('currency/options/base'),
        ));
        }

        $this->addColumn('total_qty', array(
            'header' => Mage::helper('sales')->__('Total Qty'),
            'index' => 'total_qty',
            'type'  => 'number',
        ));

        $this->addColumn('statement_date', array(
            'header' => Mage::helper('udropship')->__('Statement Ready At'),
            'index' => 'statement_date',
            'type'  => 'date',
        ));
        
        $this->addColumn('udropship_status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'udropship_status',
            'type' => 'options',
            //'renderer' => 'udpo/adminhtml_po_gridRenderer_status',
            'options' => Mage::getSingleton('udpo/source')->setPath('po_statuses')->toOptionHash(),
        ));

        $this->addColumn('action',
            array(
                'header'    => Mage::helper('sales')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('sales')->__('View'),
                        'url'     => array('base'=>'*/po/view'),
                        'field'   => 'udpo_id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        if (!Mage::getSingleton('admin/session')->isAllowed('sales/udropship/udpo')) {
            return false;
        }

        return $this->getUrl('*/po/view',
            array(
                'udpo_id'=> $row->getId(),
            )
        );
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('udpo_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem('pdf_udpos', array(
             'label'=> Mage::helper('sales')->__('PDF Purchase Orders'),
             'url'  => $this->getUrl('*/po/pdfUdpos'),
        ));

        $this->getMassactionBlock()->addItem('pesend_udpos', array(
             'label'=> Mage::helper('sales')->__('Resend Vendor PO Notification'),
             'url'  => $this->getUrl('*/po/resendUdpos'),
        ));

        Mage::dispatchEvent('udpo_adminhtml_po_grid_prepare_massaction', array('grid'=>$this));

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/*', array('_current' => true));
    }

}
