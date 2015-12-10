<?php

/**
 * Catalog product api V2
 *
 * @category   Mage
 * @author     Mihail
 */
class Fiuze_Soapapi_Model_Product_Api_V2 extends Mage_Catalog_Model_Product_Api_V2
{
    /**
     * Create new product.
     *
     * @param string $type
     * @param int $set
     * @param string $sku
     * @param array $productData
     * @param string $store
     * @return int
     */
    public function created($type, $set, $sku, $productData, $store = null)
    {
        if (!$type || !$set || !$sku) {
            $this->_fault('data_invalid');
        }

        $this->_checkProductTypeExists($type);
        $this->_checkProductAttributeSet($set);

        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product');
        $product->setStoreId($this->_getStoreId($store))
            ->setAttributeSetId($set)
            ->setTypeId($type)
            ->setSku($sku);

        if (!property_exists($productData, 'stock_data')) {
            //Set default stock_data if not exist in product data
            $_stockData = array('use_config_manage_stock' => 0);
            $product->setStockData($_stockData);
        }

        foreach ($product->getMediaAttributes() as $mediaAttribute) {
            $mediaAttrCode = $mediaAttribute->getAttributeCode();
            $product->setData($mediaAttrCode, 'no_selection');
        }

        $this->_prepareDataForSave($product, $productData);

        try {
            /**
             * @todo implement full validation process with errors returning which are ignoring now
             * @todo see Mage_Catalog_Model_Product::validate()
             */
            if (is_array($errors = $product->validate())) {
                $strErrors = array();
                foreach($errors as $code => $error) {
                    if ($error === true) {
                        $error = Mage::helper('fiuze_soapapi')->__('Attribute "%s" is invalid.', $code);
                    }
                    $strErrors[] = $error;
                }
                $this->_fault('data_invalid', implode("\n", $strErrors));
            }

            $product->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        return $product->getId();
    }

    /**
     *  Set additional data before product saved
     *
     *  @param    Mage_Catalog_Model_Product $product
     *  @param    array $productData
     *  @return   object
     */
    protected function _prepareDataForSave ($product, $productData)
    {
        
        if (property_exists($productData, 'website_ids') && is_array($productData->website_ids)) {
            $product->setWebsiteIds($productData->website_ids);
        }
        if ($product->getTypeId() == 'configurable' && property_exists($productData, 'used_configurable_attributes')) {
            
            $product->getTypeInstance()->setUsedProductAttributeIds($productData->used_configurable_attributes);
            
            $configurableAttributesData = $product->getTypeInstance()->getConfigurableAttributesAsArray();
            $product->setCanSaveConfigurableAttributes(true);
            $product->setConfigurableAttributesData($configurableAttributesData);
        }

        if (property_exists($productData, 'additional_attributes')) {
            if (property_exists($productData->additional_attributes, 'single_data')) {
                foreach ($productData->additional_attributes->single_data as $_attribute) {
                    $_attrCode = $_attribute->key;
                    $productData->$_attrCode = $_attribute->value;
                }
            }
            if (property_exists($productData->additional_attributes, 'multi_data')) {
                foreach ($productData->additional_attributes->multi_data as $_attribute) {
                    $_attrCode = $_attribute->key;
                    $productData->$_attrCode = $_attribute->value;
                }
            }
            unset($productData->additional_attributes);
        }

        foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
            $_attrCode = $attribute->getAttributeCode();

            //Unset data if object attribute has no value in current store
            if (Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID !== (int) $product->getStoreId()
                && !$product->getExistsStoreValueFlag($_attrCode)
                && !$attribute->isScopeGlobal()
            ) {
                $product->setData($_attrCode, false);
            }

            if ($this->_isAllowedAttribute($attribute) && (isset($productData->$_attrCode))) {
                $product->setData(
                    $_attrCode,
                    $productData->$_attrCode
                );
            }
        }

        if (property_exists($productData, 'categories') && is_array($productData->categories)) {
            $product->setCategoryIds($productData->categories);
        }

        if (property_exists($productData, 'websites') && is_array($productData->websites)) {
            foreach ($productData->websites as &$website) {
                if (is_string($website)) {
                    try {
                        $website = Mage::app()->getWebsite($website)->getId();
                    } catch (Exception $e) { }
                }
            }
            $product->setWebsiteIds($productData->websites);
        }

        if (Mage::app()->isSingleStoreMode()) {
            $product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        }

        if (property_exists($productData, 'stock_data')) {
            $_stockData = array();
            foreach ($productData->stock_data as $key => $value) {
                $_stockData[$key] = $value;
            }
            $product->setStockData($_stockData);
        }

        if (property_exists($productData, 'tier_price')) {
             $tierPrices = Mage::getModel('catalog/product_attribute_tierprice_api_V2')
                 ->prepareTierPrices($product, $productData->tier_price);
             $product->setData(Mage_Catalog_Model_Product_Attribute_Tierprice_Api_V2::ATTRIBUTE_CODE, $tierPrices);
        }
    }
}
