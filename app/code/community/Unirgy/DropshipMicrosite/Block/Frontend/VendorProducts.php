<?php

class Unirgy_DropshipMicrosite_Block_Frontend_VendorProducts extends Mage_Catalog_Block_Product_List
{
    protected function _getProductCollection()
    {
        if (is_null($this->_productCollection)) {
            $layer = $this->getLayer();
            /* @var $layer Mage_Catalog_Model_Layer */
            if ($this->getShowRootCategory()) {
                $this->setCategoryId(Mage::app()->getStore()->getRootCategoryId());
            }

            // if this is a product view page
            if (Mage::registry('product')) {
                // get collection of categories this product is associated with
                $categories = Mage::registry('product')->getCategoryCollection()
                    ->setPage(1, 1)
                    ->load();
                // if the product is associated with any category
                if ($categories->count()) {
                    // show products from this category
                    $this->setCategoryId(current($categories->getIterator()));
                }
            }

            $origCategory = null;
            if ($this->getCategoryId()) {
                $category = Mage::getModel('catalog/category')->load($this->getCategoryId());
                if ($category->getId()) {
                    $origCategory = $layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                }
            } elseif (!$layer->udApplied) {
                $oldIsAnchor = $layer->getCurrentCategory()->getIsAnchor();
                $layer->getCurrentCategory()->setIsAnchor(true);
            }
            $collection = $layer->getProductCollection();
            if (isset($oldIsAnchor)) {
                $layer->getCurrentCategory()->setIsAnchor($oldIsAnchor);
            }

            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());

            if ($origCategory) {
                $layer->setCurrentCategory($origCategory);
            }

            $this->_addProductAttributesAndPrices($collection);
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

            //Mage::dispatchEvent('catalog_block_product_list_collection', array('collection' => $collection));
            Mage::helper('umicrosite')->addVendorFilterToProductCollection($collection);

            $this->_productCollection = $collection;
        }
        return $this->_productCollection;
    }

    public function getLayer()
    {
        $layer = Mage::registry('current_layer');
        if ($layer) {
            return $layer;
        }
        return Mage::getSingleton('catalog/layer');
    }
}
