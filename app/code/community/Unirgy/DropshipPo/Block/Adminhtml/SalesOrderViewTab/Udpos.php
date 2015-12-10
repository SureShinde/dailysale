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
 
class Unirgy_DropshipPo_Block_Adminhtml_SalesOrderViewTab_Udpos
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('udpo_po');
        $this->setUseAjax(true);
    }

    protected function _getCollectionClass()
    {
        return 'udpo/po_grid_collection';
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass())
            ->addFieldToSelect('entity_id')
            ->addFieldToSelect('created_at')
            ->addFieldToSelect('increment_id')
            ->addFieldToSelect('total_qty')
            ->addFieldToSelect('shipping_name')
            ->addFieldToSelect('base_shipping_amount')
            ->addFieldToSelect('total_cost')
            ->addFieldToSelect('udropship_status')
            ->addFieldToSelect('udropship_vendor')
            ->addFieldToSelect('udropship_method_description')
            ->setOrderFilter($this->getOrder())
        ;
        if (Mage::helper('udropship')->isModuleActive('ustockpo')) {
            $collection->addFieldToSelect('ustock_vendor');
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', array(
            'header' => Mage::helper('sales')->__('Purchase Order #'),
            'index' => 'increment_id',
        ));

        $this->addColumn('shipping_name', array(
            'header' => Mage::helper('sales')->__('Ship to Name'),
            'index' => 'shipping_name',
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Date Created'),
            'index' => 'created_at',
            'type' => 'datetime',
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

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/udpo_view_cost')
            && Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/udpo_view_order_cost')
        ) {
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

        $this->addColumn('udropship_status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'udropship_status',
            'type' => 'options',
            //'renderer' => 'udpo/adminhtml_po_gridRenderer_status',
            'options' => Mage::getSingleton('udpo/source')->setPath('po_statuses')->toOptionHash(),
        ));

        return parent::_prepareColumns();
    }

    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function getRowUrl($row)
    {
        return $this->getUrl(
            'udpoadmin/order_po/view',
            array(
                'udpo_id'=> $row->getId(),
                'order_id'  => $row->getOrderId()
             ));
    }

    public function getGridUrl()
    {
        return $this->getUrl('udpoadmin/order_po/udposTab', array('_current' => true));
    }

    public function getTabLabel()
    {
        return Mage::helper('udpo')->__('Purchase Orders');
    }

    public function getTabTitle()
    {
        return Mage::helper('sales')->__('Purchase Orders');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}
