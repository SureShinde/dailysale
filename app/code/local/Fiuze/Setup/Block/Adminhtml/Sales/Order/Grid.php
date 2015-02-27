<?php
$name = 'Directshop_FraudDetection_Block_Adminhtml_Sales_Order_Grid';
$flag = Mage::helper('core/data')->isModuleOutputEnabled('Directshop_FraudDetection');
if (class_exists($name) && $flag) {
    class Fiuze_Setup_Block_Adminhtml_Sales_Order_Grid extends Directshop_FraudDetection_Block_Adminhtml_Sales_Order_Grid
    {
        public function __construct()
        {
            parent::__construct();
            $this->setDefaultDir('ASC');
        }

        protected function _prepareCollection()
        {
            $collection = Mage::getResourceModel($this->_getCollectionClass());
            $select = $collection->getSelect();
            $select->joinLeft('sales_flat_order', 'main_table.entity_id = sales_flat_order.entity_id', 'customer_email');
            $this->setCollection($collection);

            return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
        }

        protected function _prepareColumns()
        {
            $this->addColumn('real_order_id', array(
                'header' => Mage::helper('sales')->__('Order #'),
                'width' => '80px',
                'type' => 'text',
                'index' => 'increment_id',
                'filter_index' => 'main_table.increment_id',
            ));

            if (!Mage::app()->isSingleStoreMode()) {
                $this->addColumn('store_id', array(
                    'header' => Mage::helper('sales')->__('Purchased From (Store)'),
                    'index' => 'store_id',
                    'type' => 'store',
                    'store_view' => true,
                    'display_deleted' => true,
                    'filter_index' => 'main_table.store_id',
                ));
            }

            $this->addColumn('created_at', array(
                'header' => Mage::helper('sales')->__('Purchased On'),
                'index' => 'created_at',
                'type' => 'datetime',
                'width' => '100px',
                'filter_index' => 'main_table.created_at',
            ));

            $this->addColumn('billing_name', array(
                'header' => Mage::helper('sales')->__('Bill to Name'),
                'index' => 'billing_name',
                'filter_index' => 'main_table.billing_name',
            ));

            $this->addColumn('shipping_name', array(
                'header' => Mage::helper('sales')->__('Ship to Name'),
                'index' => 'shipping_name',
                'filter_index' => 'main_table.shipping_name',
            ));

            $this->addColumn('customer_email', array(
                'header' => Mage::helper('customer')->__('Email'),
                'index' => 'customer_email',
                'filter_index' => 'sales_flat_order.customer_email',
                'width' => '10%',
            ));

            $this->addColumn('base_grand_total', array(
                'header' => Mage::helper('sales')->__('G.T. (Base)'),
                'index' => 'base_grand_total',
                'type' => 'currency',
                'currency' => 'base_currency_code',
                'filter_index' => 'main_table.base_grand_total',
            ));

            $this->addColumn('grand_total', array(
                'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
                'index' => 'grand_total',
                'type' => 'currency',
                'currency' => 'order_currency_code',
                'filter_index' => 'main_table.grand_total',
            ));

            $this->addColumn('status', array(
                'header' => Mage::helper('sales')->__('Status'),
                'index' => 'status',
                'type' => 'options',
                'width' => '70px',
                'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
                'filter_index' => 'main_table.status',
            ));

            $this->addColumn('fraud_score', array(
                'header'=> Mage::helper('sales')->__('Fraud<br/>Score'),
                'width' => '15px',
                'type'  => 'number',
                'index' => 'fraud_score',
                'filter_condition_callback' => array($this, '_filterFraudScore'),
                'align' => 'center',
                'filter' => 'adminhtml/widget_grid_column_filter_range',
                'renderer'  => 'Directshop_FraudDetection_Block_Adminhtml_Widget_Grid_Column_Renderer_Fraudscore',
            ));

            if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
                $this->addColumn('action',
                    array(
                        'header' => Mage::helper('sales')->__('Action'),
                        'width' => '50px',
                        'type' => 'action',
                        'getter' => 'getId',
                        'actions' => array(
                            array(
                                'caption' => Mage::helper('sales')->__('View'),
                                'url' => array('base' => '*/sales_order/view'),
                                'field' => 'order_id',
                                'data-column' => 'action',
                            )
                        ),
                        'filter' => false,
                        'sortable' => false,
                        'index' => 'stores',
                        'is_system' => true,
                    ));
            }


            $this->addRssList('rss/order/new', Mage::helper('sales')->__('New Order RSS'));

            $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
            $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

            return $this;
        }
    }
} else {
    class Fiuze_Setup_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
    {
        public function __construct()
        {
            parent::__construct();
            $this->setDefaultDir('ASC');
        }

        protected function _prepareCollection()
        {
            $collection = Mage::getResourceModel($this->_getCollectionClass());
            $select = $collection->getSelect();
            $select->joinLeft('sales_flat_order', 'main_table.entity_id = sales_flat_order.entity_id', 'customer_email');
            $this->setCollection($collection);

            return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
        }

        protected function _prepareColumns()
        {
            $this->addColumn('real_order_id', array(
                'header' => Mage::helper('sales')->__('Order #'),
                'width' => '80px',
                'type' => 'text',
                'index' => 'increment_id',
                'filter_index' => 'main_table.increment_id',
            ));

            if (!Mage::app()->isSingleStoreMode()) {
                $this->addColumn('store_id', array(
                    'header' => Mage::helper('sales')->__('Purchased From (Store)'),
                    'index' => 'store_id',
                    'type' => 'store',
                    'store_view' => true,
                    'display_deleted' => true,
                    'filter_index' => 'main_table.store_id',
                ));
            }

            $this->addColumn('created_at', array(
                'header' => Mage::helper('sales')->__('Purchased On'),
                'index' => 'created_at',
                'type' => 'datetime',
                'width' => '100px',
                'filter_index' => 'main_table.created_at',
            ));

            $this->addColumn('billing_name', array(
                'header' => Mage::helper('sales')->__('Bill to Name'),
                'index' => 'billing_name',
                'filter_index' => 'main_table.billing_name',
            ));

            $this->addColumn('shipping_name', array(
                'header' => Mage::helper('sales')->__('Ship to Name'),
                'index' => 'shipping_name',
                'filter_index' => 'main_table.shipping_name',
            ));

            $this->addColumn('customer_email', array(
                'header' => Mage::helper('customer')->__('Email'),
                'index' => 'customer_email',
                'filter_index' => 'sales_flat_order.customer_email',
                'width' => '10%',
            ));

            $this->addColumn('base_grand_total', array(
                'header' => Mage::helper('sales')->__('G.T. (Base)'),
                'index' => 'base_grand_total',
                'type' => 'currency',
                'currency' => 'base_currency_code',
                'filter_index' => 'main_table.base_grand_total',
            ));

            $this->addColumn('grand_total', array(
                'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
                'index' => 'grand_total',
                'type' => 'currency',
                'currency' => 'order_currency_code',
                'filter_index' => 'main_table.grand_total',
            ));

            $this->addColumn('status', array(
                'header' => Mage::helper('sales')->__('Status'),
                'index' => 'status',
                'type' => 'options',
                'width' => '70px',
                'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
                'filter_index' => 'main_table.status',
            ));

            if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
                $this->addColumn('action',
                    array(
                        'header' => Mage::helper('sales')->__('Action'),
                        'width' => '50px',
                        'type' => 'action',
                        'getter' => 'getId',
                        'actions' => array(
                            array(
                                'caption' => Mage::helper('sales')->__('View'),
                                'url' => array('base' => '*/sales_order/view'),
                                'field' => 'order_id',
                                'data-column' => 'action',
                            )
                        ),
                        'filter' => false,
                        'sortable' => false,
                        'index' => 'stores',
                        'is_system' => true,
                    ));
            }


            $this->addRssList('rss/order/new', Mage::helper('sales')->__('New Order RSS'));

            $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
            $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

            return $this;
        }
    }


}
