<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Preview_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * @see parent
     */
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);
        $this->setId('previewProductsGrid');
        $this->setIdFieldName('entity_id');
        $this->setFilterVisibility(false);
    }

    /**
     * Use the store in the params
     *
     * @return Mage_Core_Model_Store
     */
    protected function _getStore()
    {
        $storeId = $this->getRequest()->getParam('store', 1);
        return Mage::app()->getStore($storeId);
    }

    /**
     * Prepares the product collection
     * @see parent
     */
    protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $productIds = $this->getRelatedProductIds();
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect(Mage::helper('bronto_product')->getDescriptionAttr('store', $store->getId()))
            ->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
        if (!empty($productIds)) {
            $collection->addFieldToFilter('entity_id', array('in' => $productIds));
        } else {
            $collection->addFieldToFilter('entity_id', array('eq' => 0));
        }
        if ($store->getId()) {
            $collection->addStoreFilter($store);
            $collection->joinAttribute('custom_name', 'catalog_product/name', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId());
        } else {
            $collection->addAttributeToSelect('price');
        }

        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    /**
     * Prepares the grid to match the api data
     * @see parent
     */
    protected function _prepareColumns()
    {
        $store = $this->_getStore();

        $this->addColumn('name',
            array(
                'header'=> Mage::helper('catalog')->__('Name'),
                'index' => 'name',
                'filter' => false,
                'sortable' => false
        ));

        $this->addColumn('sku',
            array(
                'header'=> Mage::helper('catalog')->__('SKU'),
                'width' => 80,
                'index' => 'sku',
                'filter' => false,
                'sortable' => false
        ));

        $this->addColumn('price',
            array(
                'header'        => Mage::helper('catalog')->__('Price'),
                'type'          => 'currency',
                'filter'        => false,
                'sortable'      => false,
                'index'         => 'price',
                'renderer'      => 'bronto_product/adminhtml_system_recommendation_grid_renderer_price',
        ));

        $this->addColumn('description',
            array(
                'header' => Mage::helper('catalog')->__('Description'),
                'width' => '200px',
                'filter' => false,
                'sortable' => false,
                'renderer' => 'bronto_product/adminhtml_system_recommendation_grid_renderer_description'
        ));

        $this->addColumn('url',
            array(
                'header' => Mage::helper('catalog')->__('URL'),
                'sortable' => false,
                'filter' => false,
                'renderer' => 'bronto_product/adminhtml_system_recommendation_grid_renderer_url'
        ));

        $this->addColumn('image_url',
            array(
                'header' => Mage::helper('catalog')->__('Image'),
                'sortable' => false,
                'filter' => false,
                'renderer' => 'bronto_product/adminhtml_system_recommendation_grid_renderer_image'
        ));

        return $this;
    }

    /**
     * Gets the related product ids to filter upon
     * @return array
     */
    public function getRelatedProductIds()
    {
        return array_keys(Mage::helper('bronto_product')->collectRecommendations(
            $this->getSelectedRecommendation(),
            $this->_getStore()->getId(),
            $this->getOptionalProducts()));
    }

    /**
     * Gets the underlying recommendation or a blank one
     * @return Bronto_Product_Model_Recommendation
     */
    public function getSelectedRecommendation()
    {
        $model = Mage::getModel('bronto_product/recommendation');
        $model->load($this->getRequest()->getParam('entity_id'));
        return $model;
    }

    /**
     * Gets the optionally selected products representing the cart or order
     * @return array
     */
    public function getOptionalProducts()
    {
        $productIds = $this->getRequest()->getPost('product_ids', array());
        $products = array();
        foreach ($productIds as $productId) {
            $products[] = Mage::getModel('catalog/product')->load($productId);
        }
        return $products;
    }
}
