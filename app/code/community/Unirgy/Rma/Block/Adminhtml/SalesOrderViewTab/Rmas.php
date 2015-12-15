<?php

class Unirgy_Rma_Block_Adminhtml_SalesOrderViewTab_Rmas
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('order_rmas');
        $this->setUseAjax(true);
    }

    protected function _getCollectionClass()
    {
        return 'urma/rma_grid_collection';
    }

    protected function _prepareCollection()
    {
        $res = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $collection->getSelect()->join(
            array('t'=>$res->getTableName('urma/rma')),
            't.entity_id=main_table.entity_id',
            array('udropship_vendor', 'rma_status', 'udropship_method',
                'udropship_method_description', 'udropship_status', 'shipping_amount'
            )
        );
        $collection->setOrderFilter($this->getOrder());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', array(
            'header'    => Mage::helper('udropship')->__('Return #'),
            'index'     => 'increment_id',
            'filter_index' => 'main_table.increment_id',
            'type'      => 'text',
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('udropship')->__('Return Created'),
            'index'     => 'created_at',
            'filter_index' => 'main_table.created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('order_increment_id', array(
            'header'    => Mage::helper('udropship')->__('Order #'),
            'index'     => 'order_increment_id',
            'type'      => 'number',
        ));

        $this->addColumn('order_created_at', array(
            'header'    => Mage::helper('udropship')->__('Order Date'),
            'index'     => 'order_created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('shipping_name', array(
            'header' => Mage::helper('udropship')->__('Shipper Name'),
            'index' => 'shipping_name',
        ));

        $this->addColumn('rma_status', array(
            'header' => Mage::helper('udropship')->__('Status'),
            'index' => 'rma_status',
            'type' => 'options',
            'options' => array(
                'pending' => Mage::helper('udropship')->__('Pending'),
                'approved' => Mage::helper('udropship')->__('Approved'),
                'declined' => Mage::helper('udropship')->__('Declined'),
            ),
        ));

        $this->addColumn('udropship_vendor', array(
            'header' => Mage::helper('udropship')->__('Vendor'),
            'index' => 'udropship_vendor',
            'type' => 'options',
            'options' => Mage::getSingleton('udropship/source')->setPath('vendors')->toOptionHash(),
            'filter' => 'udropship/vendor_gridColumnFilter'
        ));

        $this->addColumn('udropship_method_description', array(
            'header' => Mage::helper('udropship')->__('Method'),
            'index' => 'udropship_method_description',
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
            'adminhtml/urmaadmin_order_rma/view',
            array(
                'rma_id'=> $row->getId(),
                'order_id'  => $row->getOrderId()
             ));
    }

    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/urmaadmin_order_rma/rmas', array('_current' => true));
    }

    public function getTabLabel()
    {
        return Mage::helper('udropship')->__('uReturns');
    }

    public function getTabTitle()
    {
        return Mage::helper('udropship')->__('uReturns');
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
