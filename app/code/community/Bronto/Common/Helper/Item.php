<?php

class Bronto_Common_Helper_Item extends Mage_Core_Helper_Abstract
{
    private $_productCache = array();

    /**
     * Gets the product associated with this line item
     *
     * @param Mage_Sales_Model_Quote_Item | Mage_Sales_Model_Order_Item
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct($lineItem, $includeParent = true)
    {
        if ($includeParent) {
            $lineItem = $this->getParentItem($lineItem);
        }
        if (!isset($this->_productCache[$lineItem->getProductId()])) {
            $this->_productCache[$lineItem->getProductId()] = Mage::getModel('catalog/product')
                ->setStoreId($lineItem->getStoreId())
                ->load($lineItem->getProductId());
        }
        return $this->_productCache[$lineItem->getProductId()];
    }

    public function getParentItem($lineItem)
    {
        if ($lineItem->getParentItemId()) {
            return $lineItem->getParentItem();
        }
        return $lineItem;
    }

    /**
     * Gets the product url for a given line item
     *
     * @param Mage_Sales_Model_Quote_Item | Mage_Sales_Model_Order_Item
     * @return string
     */
    public function getProductUrl($lineItem)
    {
        if ($lineItem->getRedirectUrl()) {
            return $lineItem->getRedirectUrl();
        }
        $helper = Mage::helper('bronto_common/product');
        $product = $this->getProduct($lineItem, false);
        if (!$helper->isVisibleInidividually($product)) {
            $product = $this->getProduct($lineItem);
        }
        if (!$helper->isVisibleInidividually($product)) {
            $product = $helper->getGroupedProduct($product);
        }
        return Mage::helper('catalog/product')->getProductUrl($product);
    }

    /**
     * Gets the product image url
     *
     * @param Mage_Sales_Model_Quote_Item | Mage_Sales_Model_Order_Item
     * @return string
     */
    public function getImage($lineItem)
    {
        $helper = Mage::helper('bronto_common/product');
        $product = $this->getProduct($lineItem, false);
        $image = Mage::helper('bronto_common')->getProductImageUrl($product);
        // No image, get the parent
        if (preg_match('|/placeholder/|', $image)) {
            $product = $this->getProduct($lineItem);
            if (!$helper->isVisibleInidividually($product)) {
                $product = $helper->getGroupedProduct($product);
            }
            return Mage::helper('bronto_common')->getProductImageUrl($product);
        }
        return $image;
    }

    public function getDescription($lineItem)
    {
        return $this->getProduct($lineItem)->getDescription();
    }

    public function getName($lineItem)
    {
        return $this->getParentItem($lineItem)->getName();
    }

    public function getFlatItems($object)
    {
        $index = null;
        $lineItems = array();
        foreach ($object->getAllItems() as $lineItem) {
            if (method_exists($lineItem, 'getOrderItem')) {
                $lineItem = $lineItem->getOrderItem();
            }
            if ($lineItem->getParentItemId()) {
                $lineItems[$index] = $lineItem;
            } else {
                $lineItems[] = $lineItem;
                if (is_null($index)) {
                    $index = 0;
                } else {
                    $index++;
                }
            }
        }
        return $lineItems;
    }

    /**
     * Gets the qty of the lineItem
     *
     * @param Mage_Sales_Model_Quote_Item | Mage_Sales_Model_Order_Item
     * @return float
     */
    public function getQty($lineItem)
    {
        if ($lineItem instanceof Mage_Sales_Model_Order_Item) {
            return $this->getParentItem($lineItem)->getQtyOrdered();
        } else {
            return $this->getParentItem($lineItem)->getQty();
        }
    }
}
