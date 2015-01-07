<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Selected_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * @see parent
     */
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);
        $this->setId('selectedProductsGrid');
        $this->setIdFieldName('entity_id');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        if ($this->_getSelectedProducts()) {
            $this->setDefaultFilter(array('in_products' => 1));
        }
    }

    /**
     * Initial column filter for pre-existing products
     *
     * @param mixed $column
     * @return Bronto_Product_Block_Adminhtml_System_Recommendation_Selected_Grid
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'in_products') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', array('in'=>$productIds));
            } else {
                if($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', array('nin'=>$productIds));
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Use the store in the params
     *
     * @return Mage_Core_Model_Store
     */
    protected function _getStore()
    {
        $storeId = $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    /**
     * Product collection for the products
     *
     * @see parent
     */
    protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id')
            //->addAttributeToFilter('status', array(''))
            ->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');

        if ($store->getId()) {
            $collection->addStoreFilter($store);
            $collection->joinAttribute('custom_name', 'catalog_product/name', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId());
        } else {
            $collection->addAttributeToSelect('price');
            $collection->addAttributeToSelect('status');
            $collection->addAttributeToSelect('visibility');
        }

        $this->setCollection($collection);

        parent::_prepareCollection();
        $this->getCollection()->addWebsiteNamesToResult();
        return $this;
    }

    /**
     * Prepares the columns used in the product grid
     *
     * @see parent
     */
    protected function _prepareColumns()
    {
        $this->addColumn('in_products', array(
            'header_css_class'  => 'a-center',
            'type'              => 'checkbox',
            'field_name'        => 'in_products',
            'values'            => $this->_getSelectedProducts(),
            'align'             => 'center',
            'index'             => 'entity_id'
        ));

        $this->addColumn('entity_id',
            array(
                'header'=> Mage::helper('catalog')->__('ID'),
                'width' => 50,
                'sortable'  => true,
                'type'  => 'number',
                'index' => 'entity_id',
        ));
        $this->addColumn('name',
            array(
                'header'=> Mage::helper('catalog')->__('Name'),
                'index' => 'name',
        ));

        $store = $this->_getStore();
        if ($store->getId()) {
            $this->addColumn('custom_name',
                array(
                    'header'=> Mage::helper('catalog')->__('Name in %s', $store->getName()),
                    'index' => 'custom_name',
            ));
        }

        $this->addColumn('type',
            array(
                'header'    => Mage::helper('catalog')->__('Type'),
                'width'     => 100,
                'index'     => 'type_id',
                'type'      => 'options',
                'options'   => Mage::getSingleton('catalog/product_type')->getOptionArray(),
        ));

        $sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
            ->load()
            ->toOptionHash();

        $this->addColumn('set_name',
            array(
                'header'    => Mage::helper('catalog')->__('Attrib. Set Name'),
                'width'     => 100,
                'index'     => 'attribute_set_id',
                'type'      => 'options',
                'options'   => $sets,
        ));

        $this->addColumn('sku',
            array(
                'header'=> Mage::helper('catalog')->__('SKU'),
                'width' => 80,
                'index' => 'sku',
        ));

        $store = $this->_getStore();
        $this->addColumn('price',
            array(
                'header'        => Mage::helper('catalog')->__('Price'),
                'type'          => 'price',
                'currency_code' => $store->getBaseCurrency()->getCode(),
                'index'         => 'price',
        ));

        $this->addColumn('visibility',
            array(
                'header'    => Mage::helper('catalog')->__('Visibility'),
                'width'     => 100,
                'index'     => 'visibility',
                'type'      => 'options',
                'options'   => Mage::getModel('catalog/product_visibility')->getOptionArray(),
        ));

        $this->addColumn('status',
            array(
                'header'    => Mage::helper('catalog')->__('Status'),
                'width'     => 70,
                'index'     => 'status',
                'type'      => 'options',
                'options'   => Mage::getSingleton('catalog/product_status')->getOptionArray(),
        ));

        return parent::_prepareColumns();
    }

    /**
     * Gets the selected products from the request or model
     *
     * @return array
     */
    protected function _getSelectedProducts()
    {
        $products = $this->getRequest()->getParam('products', null);
        Mage::helper('bronto_common')->writeDebug(var_export($products, true));
        if (!is_array($products)) {
            $products = $this->getRelatedProductIds();
        }
        return $products;
    }

    /**
     * Gets the related products from the source of the model
     *
     * @return array
     */
    public function getRelatedProductIds()
    {
        $source = $this->getRequest()->getParam('source', 'primary');
        $product = Mage::registry('current_product_recommendation');
        return $product->getCustomProductIds($source);
    }
}
