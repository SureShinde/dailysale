<?php

class Unirgy_Rma_Block_Adminhtml_Rma_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('urma_rma_grid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
    }

    protected function _getCollectionClass()
    {
        return 'urma/rma_grid_collection';
    }

    public function t($table)
    {
        return Mage::getSingleton('core/resource')->getTableName($table);
    }

    protected function _getFlatExpressionColumn($key, $bypass=true)
    {
        $result = $bypass ? $key : null;
        switch ($key) {
            case 'tracking_price':
                $result = new Zend_Db_Expr("(select sum(IFNULL(st.final_price,0)) from {$this->t('urma/rma_track')} st where st.parent_id=main_table.entity_id)");
                break;
        }
        return $result;
    }

    protected function _prepareCollection()
    {
        $res = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $collection->getSelect()->join(
            array('t'=>$res->getTableName('urma/rma')),
            't.entity_id=main_table.entity_id',
            array('udropship_vendor', 'rma_status', 'udropship_method',
                'udropship_method_description', 'udropship_status', 'shipping_amount',
                'tracking_price'=>$this->_getFlatExpressionColumn('tracking_price')

            )
        );
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
            'options' => Mage::helper('urma')->getVendorRmaStatuses(),
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

        $this->addColumn('tracking_price', array(
            'header' => Mage::helper('udropship')->__('Tracking Price'),
            'index' => 'tracking_price',
            'filter_index' => $this->_getFlatExpressionColumn('tracking_price'),
            'type'  => 'price',
            'currency_code' => Mage::getStoreConfig('currency/options/base'),
        ));

        $this->addColumn('action',
            array(
                'header'    => Mage::helper('udropship')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('udropship')->__('View'),
                        'url'     => array('base'=>'*/rma/view'),
                        'field'   => 'rma_id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('udropship')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('udropship')->__('Excel'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        if (!Mage::getSingleton('admin/session')->isAllowed('sales/urma')) {
            return false;
        }

        return $this->getUrl('*/urmaadmin_rma/view',
            array(
                'rma_id'=> $row->getId(),
            )
        );
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/*', array('_current' => true));
    }

}
