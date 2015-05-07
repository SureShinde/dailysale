<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Helper_Data extends Bronto_Common_Helper_Data implements Bronto_Common_Helper_DataInterface
{
    const XML_PATH_ENABLED       = 'bronto_order/settings/enabled';
    const XML_PATH_MAGE_CRON     = 'bronto_order/settings/mage_cron';
    const XML_PATH_LIMIT         = 'bronto_order/settings/limit';
    const XML_PATH_SYNC_LIMIT    = 'bronto_order/settings/sync_limit';
    const XML_PATH_INSTALL_DATE  = 'bronto_order/settings/install_date';
    const XML_PATH_UPGRADE_DATE  = 'bronto_order/settings/upgrade_date';
    const XML_PATH_BULK_LIMIT    = 'bronto_order/settings/bulk_limit';
    const XML_PATH_IMPORT_STATES = 'bronto_order/settings/import_states';
    const XML_PATH_DELETE_STATES = 'bronto_order/settings/delete_states';

    const XML_PATH_PRICE         = 'bronto_order/import/price';
    const XML_PATH_DESCRIPTION   = 'bronto_order/import/description';
    const XML_PATH_INCL_DISCOUNT = 'bronto_order/import/incl_discount';
    const XML_PATH_INCL_TAX      = 'bronto_order/import/incl_tax';
    const XML_PATH_INCL_SHIPPING = 'bronto_order/import/incl_shipping';

    const XML_PATH_CRON_STRING   = 'crontab/jobs/bronto_order_import/schedule/cron_expr';
    const XML_PATH_CRON_MODEL    = 'crontab/jobs/bronto_order_import/run/model';

    /**
     * Module Human Readable Name
     */
    protected $_name = 'Bronto Order Import';

    /**
     * Get Human Readable Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->__($this->_name);
    }

    /**
     * Check if module is enabled
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return bool
     */
    public function isEnabled($scope = 'default', $scopeId = 0)
    {
        // Get Enabled Scope
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_ENABLED, $scope, $scopeId);
    }
    /**
     * Disable Specified Module
     *
     * @param string $scope
     * @param int    $scopeId
     * @param bool   $deleteConfig
     *
     * @return bool
     */
    public function disableModule($scope = 'default', $scopeId = 0, $deleteConfig = false)
    {
        return $this->_disableModule(self::XML_PATH_ENABLED, $scope, $scopeId, $deleteConfig);
    }

    /**
     * @return int
     */
    public function getSyncLimit()
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_SYNC_LIMIT);
    }

    /**
     * Get Send Limit
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return int
     */
    public function getLimit($scope = 'default', $scopeId = 0)
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_LIMIT, $scope, $scopeId);
    }

    /**
     * Check if module can use the magento cron
     *
     * @return bool
     */
    public function canUseMageCron()
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_MAGE_CRON, 'default', 0);
    }

    /**
     * Gets the valid import states
     *
     * @return array
     */
    public function getImportStates($scope = 'default', $scopeId = 0)
    {
        $states = $this->getAdminScopedConfig(self::XML_PATH_IMPORT_STATES, $scope, $scopeId);
        if (is_string($states)) {
            $states = explode(',', $states);
        }
        return $states;
    }

    /**
     * Gets the valid delete states
     *
     * @return array
     */
    public function getDeleteStates($scope = 'default', $scopeId = 0)
    {
        $states = $this->getAdminScopedConfig(self::XML_PATH_DELETE_STATES, $scope, $scopeId);
        if (is_string($states)) {
            $states = explode(',', $states);
        }
        return $states;
    }

    /**
     * @return string
     */
    public function getCronStringPath()
    {
        return self::XML_PATH_CRON_STRING;
    }

    /**
     * @return string
     */
    public function getCronModelPath()
    {
        return self::XML_PATH_CRON_MODEL;
    }

    /**
     * @return string
     */
    public function getDescriptionAttribute()
    {
        return $this->getAdminScopedConfig(self::XML_PATH_DESCRIPTION);
    }

    /**
     * @return string
     */
    public function getPriceAttribute($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_PRICE, $scope, $scopeId);
    }

    /**
     * @return boolean
     */
    public function isTaxIncluded($scope = 'default', $scopeId = 0)
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_INCL_TAX, $scope, $scopeId);
    }

    /**
     * @return boolean
     */
    public function isShippingIncluded($scope = 'default', $scopeId = 0)
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_INCL_SHIPPING, $scope, $scopeId);
    }

    /**
     * @return boolean
     */
    public function isDiscountIncluded($scope = 'default', $scopeId = 0)
    {
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_INCL_DISCOUNT, $scope, $scopeId);
    }

    /**
     * @return int
     */
    public function getBulkLimit($scope = 'default', $scopeId = 0) {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_BULK_LIMIT, $scope, $scopeId);
    }

    /**
     * Gets the tid hash for the managed tid
     *
     * @return string
     */
    public function getTidKey()
    {
        return md5(
            Mage::app()->getStore()->getWebsiteId() .
            Mage::getConfig()->getNode(Mage_Core_Model_App::XML_PATH_INSTALL_DATE)
        );
    }

    /**
     * Gets the item price
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param string $priceAttr
     * @param boolean $inclTaxes
     * @param boolean $inclDiscounts
     * @return float
     */
    public function getItemPrice($item, $priceAttr, $inclTaxes, $inclDiscounts)
    {
        $base = $priceAttr == 'base' ? 'base_' : '';
        $rowTotal = $item->getData("{$base}row_total");
        $quantity = $item->getQtyOrdered();
        if ($inclTaxes) {
            $rowTotal += $item->getData("{$base}tax_amount");
        }
        if ($inclDiscounts) {
            $rowTotal -= $item->getData("{$base}discount_amount");
        }
        return !empty($quantity) ? max((float)($rowTotal / $quantity), 0.00) : 0.00;
    }

    /**
     * Retrieve helper module name
     *
     * @return string
     */
    protected function _getModuleName()
    {
        return 'Bronto_Order';
    }

    /**
     * Get Item Product Url
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param Mage_Catalog_Model_Product  $itemProduct
     * @param bool                        $storeId
     *
     * @return mixed
     */
    public function getItemUrl(Mage_Sales_Model_Order_Item $item, Mage_Catalog_Model_Product $itemProduct, $storeId = false)
    {
        $productId = $this->_getIdToUse($item, $itemProduct);

        return Mage::helper('bronto_common/product')->getProductAttribute($productId, 'url', $storeId);
    }

    /**
     * Get Item image
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param Mage_Catalog_Model_Product  $itemProduct
     * @param bool                        $storeId
     *
     * @return mixed]
     */
    public function getItemImg(Mage_Sales_Model_Order_Item $item, Mage_Catalog_Model_Product $itemProduct, $storeId = false)
    {
        $attribute = Mage::helper('bronto_common/product')->getProductAttribute($itemProduct->getId(), 'image', $storeId);
        if ($attribute) {
            return $attribute;
        }

        $productId = $this->_getIdToUse($item, $itemProduct, false);

        return Mage::helper('bronto_common/product')->getProductAttribute($productId, 'image', $storeId);
    }

    /**
     * Get the product ID to use based on Item visibility
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param Mage_Catalog_Model_Product  $itemProduct
     * @param boolean                     $checkVisible
     *
     * @return int
     */
    protected function _getIdToUse(Mage_Sales_Model_Order_Item $item, Mage_Catalog_Model_Product $itemProduct, $checkVisible = true)
    {
        if ($checkVisible && in_array($itemProduct->getVisibility(), array('2', '4'))) {
            return $item->getProductId();
        } else {
            $superProductConfig = $this->_getSuperProductConfig($item);
            if ($superProductConfig && array_key_exists('product_id', $superProductConfig)) {
                return $superProductConfig['product_id'];
            } elseif (method_exists($item, 'getParentItemId')) {
                return $item->getParentItemId();
            } else {
                return $item->getProductId();
            }
        }
    }

    /**
     * This function gets the order item's info_buyRequest super_product_config values
     * if they exist
     *
     * @param Mage_Sales_Model_Order_Item $item
     *
     * @return boolean|array
     * @access protected
     */
    protected function _getSuperProductConfig(Mage_Sales_Model_Order_Item $item)
    {
        if (method_exists($item, 'getBuyRequest')) {
            $buyRequest = $item->getBuyRequest()->getData();
        } elseif (method_exists($item, 'getProductOptionByCode')) {
            $buyRequest = $item->getProductOptionByCode('info_buyRequest');
        } elseif (method_exists($item, 'getProductOptions')) {
            $options    = $item->getProductOptions();
            $buyRequest = $options['info_buyRequest'];
        } elseif (method_exists($item, 'getOptionByCode')) {
            $buyRequest = $item->getOptionByCode('info_buyRequest');
        } else {
            return false;
        }

        if ($buyRequest && array_key_exists('super_product_config', $buyRequest)) {
            return $buyRequest['super_product_config'];
        } elseif ($buyRequest && array_key_exists('product', $buyRequest)) {
            return array('product_id' => $buyRequest['product']);
        }

        return false;
    }

    /**
     * Get Count of orders not in queue
     *
     * @return int
     */
    public function getMissingOrdersCount()
    {
        return Mage::getModel('bronto_order/queue')
            ->getMissingOrdersCount();
    }

    /**
     * Get Orders which aren't in contact queue
     *
     * @return array
     */
    public function getMissingOrders()
    {
        return Mage::getModel('bronto_order/queue')
            ->getMissingOrders();
    }
}
