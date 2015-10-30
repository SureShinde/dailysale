<?php

/* Product REST API
 *
 * @category  GoDataFeed
 * @package   GoDataFeed_Restapi
 */

class GoDataFeed_Restapi_Model_Api2_Product_Rest_Guest_V1 extends Mage_Catalog_Model_Api2_Product_Rest {
 /**#@-*/

    /**#@+
     * Common operations for attributes
     */
    const OPERATION_ATTRIBUTE_READ  = 'read';
  
    /**#@-*/

    /**#@+
     *  Default error messages
     */
    const RESOURCE_NOT_FOUND = 'Resource not found.';
    const RESOURCE_METHOD_NOT_ALLOWED = 'Resource does not support method.';
    const RESOURCE_METHOD_NOT_IMPLEMENTED = 'Resource method not implemented yet.';
    const RESOURCE_INTERNAL_ERROR = 'Resource internal error.';
    const RESOURCE_DATA_PRE_VALIDATION_ERROR = 'Resource data pre-validation error.';
    /**#@-*/

    /**#@+
     *  Default collection resources error messages
     */
    const RESOURCE_COLLECTION_PAGING_ERROR       = 'Resource collection paging error.';
    const RESOURCE_COLLECTION_PAGING_LIMIT_ERROR = 'The paging limit exceeds the allowed number.';
    const RESOURCE_COLLECTION_ORDERING_ERROR     = 'Resource collection ordering error.';
   
    /**#@-*/

    /**#@+
     * Collection page sizes
     */
    const PAGE_SIZE_DEFAULT = 10;
    const PAGE_SIZE_MAX     = 5000; 
    // return produt count.
    protected function _retrieve() {

        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $store = $this->_getStore();
        $collection->setStoreId($store->getId());
        $collection->addAttributeToSelect(array_keys(
        $this->getAvailableAttributes($this->getUserType(), Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ)
        ));
        $this->_applyCategoryFilter($collection);
        $this->_applyCollectionCustomModifiers($collection);
        $collection->load()->toArray();
        $size = $collection->getSize();
        return $size;
    }
    
    
    /**
     * Retrieve list of product data
     *
     * @return array
     */
    protected function _retrieveCollection() {
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
      //  $type_id = $this->getRequest()->getParam('type_id');
        $collection = Mage::getResourceModel('catalog/product_collection');
        $store = $this->_getStore();
        $entityOnlyAttributes = $this->getEntityOnlyAttributes($this->getUserType(), Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ);
        $availableAttributes = array_keys($this->getAvailableAttributes($this->getUserType(), Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ));
        // available attributes not contain image attribute, but it needed for get image_url
        $availableAttributes[] = 'image';
        $collection->addStoreFilter($store->getId())
                ->addPriceData($this->_getCustomerGroupId(), $store->getWebsiteId())
                ->addAttributeToSelect(array_diff($availableAttributes, $entityOnlyAttributes));
       // if($type_id){
       //    $collection->addAttributeToFilter('type_id',$type_id);
      //  }       
        $this->_applyCategoryFilter($collection);
        $this->_applyCollectionCustomModifiers($collection);
        $products = $collection->load();

        /** @var Mage_Catalog_Model_Product $product */
        foreach ($products as $product) {
            $this->_setProduct($product);
            $this->_prepareProductForResponse($product);
        }
        return $products->toArray();
    }

    protected function _prepareProductForResponse(Mage_Catalog_Model_Product $product) {
              /** @var $productHelper Mage_Catalog_Helper_Product */
        $productHelper = Mage::helper('catalog/product');
        $productData = $product->getData();
        //print_r($productData);die();
        $product->setWebsiteId($this->_getStore()->getWebsiteId());
        // customer group is required in product for correct prices calculation
        $product->setCustomerGroupId($this->_getCustomerGroupId());
        // calculate prices
        $finalPrice = $product->getFinalPrice();
        $productData['price'] = $finalPrice;
        $productData['shipping_price'] = number_format($product->getShippingAmount(), 0, '.', '');
        $productData['weight'] = $product->getWeight();
        $attr_gen = $product->getResource()->getAttribute('gender');
        if ($attr_gen) {
            $gender_value = $attr_gen->getSource()->getOptionText($product->getGender());
            $productData['gender'] = $gender_value ? $gender_value : NULL;
        }
        $attr_col = $product->getResource()->getAttribute('color');
        if ($attr_col) {
            $color_value = $attr_col->getSource()->getOptionText($product->getColor());
            $productData['color'] = $color_value ? $color_value : NULL;
        }
        $attr_size = $product->getResource()->getAttribute('size');
        if ($attr_size) {
            $size_value = $attr_size->getSource()->getOptionText($product->getSize());
            $productData['size'] = $size_value ? $size_value : NULL;
        }
        $attr_brand = $product->getResource()->getAttribute('brand');
        if ($attr_brand) {
            $brand_value = $attr_brand->getSource()->getOptionText($product->getBrand());
            $productData['brand'] = $brand_value ? $brand_value : NULL;
        }
        $attr_unit = $product->getResource()->getAttribute('unit');
        if ($attr_unit) {
            $unit_value = $attr_unit->getSource()->getOptionText($product->getUnit());
            $productData['unit'] = $unit_value ? $unit_value : NULL;
        }
        $productData['meta_keyword'] = $product->getMetaKeyword();
        $productData['special_price'] = $product->getSpecialPrice();
        $productData['tax'] = $this->_applyTaxToPrice($finalPrice, true) - $this->_applyTaxToPrice($finalPrice, false);
        $parentIds = 0;
        if ($product->getTypeId() == "simple") {
            $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
            if (!$parentIds)
                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
        }
        $productData['parent_product_id'] = implode('|', $parentIds);
        $productData['is_saleable'] = $product->getIsSalable();
        $base_image_url = Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage());
        $productData['base_image_url'] = $base_image_url;
        $gallery_images = array();
        $product_obj = Mage::getModel('catalog/product')->load($product->getId());
        foreach ($product_obj->getMediaGalleryImages() as $image) {
            $gallery_images[] = $image->getUrl();
        }
        if (($key = array_search($base_image_url, $gallery_images)) !== false) {
            unset($gallery_images[$key]);
        }
        $productData['gallery_images'] = array_values($gallery_images);
        $cats = $product->getCategoryIds();
        $productData['category_id'] = implode('|', $cats);
        $category_name = array();
        $category_parent_id = array();
        $category_parent_name = array();
        $category_parent_name_arr = array();
        foreach ($cats as $category_id) {       
            $_cat = Mage::getModel('catalog/category')->load($category_id);
            $category_name[] = $_cat->getName();
            $path = $_cat->getPath();
            $ids = explode('/', $path);
            array_shift($ids);
            $category_parent_id[] = implode('|', $ids);
            foreach ($ids as $key => $value) {
                $_category = Mage::getModel('catalog/category')->load($value);
                $category_parent_name[$key] = $_category->getName();
            }
           $category_parent_name_arr[] = implode('|', $category_parent_name);
        }
        $productData['category_name'] = implode('|', $category_name);
        $productData['category_parent_id'] =  $category_parent_id;
        $productData['category_parent_name'] = $category_parent_name_arr;
        // define URLs
        $productData['url'] = $productHelper->getProductUrl($product->getId());
        /** @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
        $stockItem = Mage::getModel('cataloginventory/stock_item');
        $stockItem->loadByProduct($product);
        $productData['is_in_stock'] = $stockItem->getIsInStock();
        $productData['quantity'] = number_format($stockItem->getQty(), 0, '.', '');
        $product->addData($productData);
    }
  
  protected function _applyCollectionCustomModifiers(Varien_Data_Collection_Db $collection)
    {
       
        $pageNumber = $this->getRequest()->getPageNumber();
        if ($pageNumber != abs($pageNumber)) {
            $this->_critical(self::RESOURCE_COLLECTION_PAGING_ERROR);
        }
        $pageSize = $this->getRequest()->getPageSize();
        if (null == $pageSize) {
            $pageSize = self::PAGE_SIZE_DEFAULT;
        } else {
            if ($pageSize != abs($pageSize) || $pageSize > self::PAGE_SIZE_MAX) {
                $this->_critical(self::RESOURCE_COLLECTION_PAGING_LIMIT_ERROR);
            }
        }
        $orderField = $this->getRequest()->getOrderField();
        if (null !== $orderField) {
            $operation = Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ;
            if (!is_string($orderField)
                || !array_key_exists($orderField, $this->getAvailableAttributes($this->getUserType(), $operation))
            ) {
                $this->_critical(self::RESOURCE_COLLECTION_ORDERING_ERROR);
            }
            $collection->setOrder($orderField, $this->getRequest()->getOrderDirection());
        }
        $collection->setCurPage($pageNumber)->setPageSize($pageSize);
        return $this->_applyFilter($collection);
    }

}

?>