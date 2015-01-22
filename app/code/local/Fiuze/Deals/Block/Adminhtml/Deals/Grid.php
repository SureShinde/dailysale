<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author      Webinse Team <info@webinse.com>
 */
class Fiuze_Deals_Block_Adminhtml_Deals_Grid extends Mage_Adminhtml_Block_Widget_Grid{
    /**
     * Set some default on the grid
     */
    public function __construct(){
        parent::__construct();
        $this->setId('dealsGrid');
        $this->setDefaultSort('identifier');
        $this->setDefaultDir('ASC');
    }

    protected function getStore(){
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    /**
     * Set the desired collection on our grid
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection(){
        $currentCategory = Mage::helper('fiuze_deals')->getCategoryCron()->getProductCollection();
        $currentCategory->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->joinField(
                'qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )
            ->addAttributeToFilter('qty', array("gt" => 0))
            ->addAttributeToSelect('*');

        $tableName = Mage::getSingleton('core/resource')->getTableName('fiuze_deals/deals');
        $currentCategory->getSelect()->joinLeft($tableName, 'e.entity_id = ' . $tableName . '.product_id',
            array('deals_active', 'deals_price', 'deals_qty', 'origin_special_price', 'sort_order')
        );

        $this->setCollection($currentCategory);
        return parent::_prepareCollection();
    }

    /**
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns(){
        $store = $this->getStore();
        $helper = Mage::helper('fiuze_deals');
        $tableName = Mage::getSingleton('core/resource')->getTableName('fiuze_deals/deals');
        $this->addColumn('product_name', array(
            'header' => $helper->__('Product Name'),
            'index' => 'name',
            'width' => '30%',
        ));

        $this->addColumn('product_sku', array(
            'header' => $helper->__('Product Sku'),
            'index' => 'sku',
            'width' => '10%',
        ));

        $this->addColumn('price', array(
            'header' => $helper->__('Price'),
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'type' => 'price',
            'index' => 'price',
            'width' => '5%',
        ));

        $this->addColumn('origin_special_price', array(
            'header' => $helper->__('Origin special price'),
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'type' => 'price',
            'index' => 'origin_special_price',
            'width' => '5%',
            'filter' => false,
            'sortable' => false,
        ));

        $this->addColumn('deals_price', array(
            'header' => $helper->__('Deal price'),
            'type' => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'index' => 'deals_price',
            'width' => '5%',
            'filter' => false,
            'sortable' => false,
        ));

        $this->addColumn('qty', array(
            'header' => $helper->__('Quantity'),
            'type' => 'number',
            'index' => 'qty',
        ));

        $this->addColumn('deals_qty', array(
            'header' => $helper->__('Deal Quantity'),
            'type' => 'number',
            'index' => 'deals_qty',
            'filter' => false,
            'sortable' => false,
        ));

        $this->addColumn('sort_order', array(
            'header' => $helper->__('Sort order'),
            'type' => 'number',
            'index' => 'sort_order',
            'filter' => false,
            'sortable' => false,
        ));

        $this->addColumn('deals_active', array(
            'header' => Mage::helper('catalog')->__('Deal status'),
            'width' => '70px',
            'index' => 'deals_active',
            'type' => 'options',
            'filter' => false,
            'sortable' => false,
            'options' => Mage::getModel('fiuze_deals/system_config_source_status')->toArray(),
            'renderer' => 'Fiuze_Deals_Block_Adminhtml_Widget_Grid_Column_Renderer_Status',
            'align' => 'center'
        ));

        $this->addColumn('view_deal_product', array(
            'header' => $helper->__('Edit'),
            'type' => 'action',
            'getter' => 'getId',
            'filter' => false,
            'sortable' => false,
            'is_system' => true,
            'width' => '5%',
            'actions' => array(
                array(
                    'caption' => $helper->__('Edit'),
                    'url' => array('base' => '*/*/edit/'),
                    'field' => 'id')),
        ));

        $this->addColumn('view_product', array(
            'header' => $helper->__('View Product'),
            'width' => '5%',
            'type' => 'action',
            'getter' => 'getId',
            'filter' => false,
            'sortable' => false,
            'is_system' => true,
            'actions' => array(
                array(
                    'caption' => $helper->__('View Product'),
                    'url' => array('base' => '*/catalog_product/edit/'),
                    'field' => 'id')),
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row){
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    public function getGridUrl(){
        return $this->getUrl('*/*/list', array('_current' => true));
    }
}