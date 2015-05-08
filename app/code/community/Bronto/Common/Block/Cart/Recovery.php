<?php

class Bronto_Common_Block_Cart_Recovery extends Mage_Core_Block_Template
{
    private $_quote;
    private $_order;
    private $_displayOrder = false;
    private $_categoryCache = array();
    private $_productCache = array();

    /**
     * Get the Cart Recovery Javascript
     *
     * @return string
     */
    public function getCartRecoveryCode()
    {
        return Mage::helper('bronto_common')->getCartRecoveryCode();
    }

    /**
     * Get the line item attribute code
     *
     * @return string
     */
    public function getLineItemAttributeCode()
    {
        return Mage::helper('bronto_common')->getLineItemAttributeCode();
    }

    /**
     * Get the checkout session containing cart and order data
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Gets the checkout url for the cart
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        $quote = $this->getQuote();
        if ($quote) {
            $quoteId = urlencode(base64_encode(Mage::helper('core')->encrypt($quote->getId())));
            return Mage::app()->getStore()->getUrl('reminder/load', array('id' => $quoteId));
        }
        return Mage::app()->getStore()->getUrl('checkout/cart');
    }

    /**
     * Sets the display order flag
     *
     * @param $value
     */
    public function setDisplayOrder($value)
    {
        $this->_displayOrder = (boolean)((int) $value);
    }

    /**
     * Gets the cart in the session
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if (is_null($this->_quote)) {
            $this->_quote = false;
            if ($this->getCheckout()->hasQuote()) {
                $this->_quote = $this->getCheckout()->getQuote();
            }
        }
        return $this->_quote;
    }

    /**
     * Gets the order that was placed
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (is_null($this->_order)) {
            $this->_order = false;
            $orderId = $this->getCheckout()->getLastOrderId();
            if ($this->_displayOrder && $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
                if ($order->getId()) {
                    $this->_order = $order;
                }
            }
        }
        return $this->_order;
    }

    /**
     * Gets the applicable sales object
     *
     * @return Mage_Sales_Model_Quote | Mage_Sales_Model_Order
     */
    public function getSalesObject()
    {
        if ($this->getOrder()) {
            return $this->getOrder();
        } else if ($this->getQuote() && $this->getQuote()->getId()) {
            return $this->getQuote();
        } else {
            return false;
        }
    }

    /**
     * Formats the categories into reasonable display names
     *
     * @param Mage_Sales_Model_Quote_Item | Mage_Sales_Model_Order_Item
     * @return string
     */
    public function renderCategories($lineItem)
    {
        $categories = array();
        $product = $this->getProduct($lineItem);
        foreach ($product->getCategoryIds() as $categoryId) {
            if (isset($this->_categoryCache[$categoryId])) {
                $category = $this->_categoryCache[$categoryId];
            } else {
                $category = Mage::getModel('catalog/category')->load($categoryId);
                $this->_categoryCache[$categoryId] = $category;
            }
            $parent = $category->getParentCategory();
            $categories[] = $parent->getUrlKey() ?
                $parent->getUrlKey() :
                $parent->formatUrlKey($parent->getName());
            $categories[] = $category->getUrlKey() ?
                $category->getUrlKey() :
                $category->formatUrlKey($category->getName());
        }
        $categories = array_unique($categories);
        return implode(' ', $categories);
    }

    /**
     * Gets the product associated with this line item
     *
     * @param Mage_Sales_Model_Quote_Item | Mage_Sales_Model_Order_Item
     * @return Mage_Catalog_Model_Product
     */
    private function getProduct($lineItem)
    {
        if (!isset($this->_productCache[$lineItem->getProductId()])) {
            $this->_productCache[$lineItem->getProductId()] = Mage::getModel('catalog/product')
                ->setStoreId($this->getSalesObject()->getStoreId())
                ->load($lineItem->getProductId());
        }
        return $this->_productCache[$lineItem->getProductId()];
    }

    /**
     * Gets the product description
     *
     * @param Mage_Sales_Model_Quote_Item | Mage_Sales_Model_Order_Item
     * @return string
     */
    public function getDescription($lineItem)
    {
        return $this->getProduct($lineItem)->getDescription();
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
        return Mage::helper('catalog/product')->getProductUrl($this->getProduct($lineItem));
    }

    /**
     * Gets the other attribute value
     *
     * @return mixed
     */
    public function getOther($lineItem)
    {
        $product = $this->getProduct($lineItem);
        $attributeCode = $this->getLineItemAttributeCode();
        if ($attributeCode) {
            $attributeValue = $product->getData($attributeCode);
            if (!is_null($attributeValue)) {
                $attribute = $product->getResource()->getAttribute($attributeCode);
                if ($attribute->getFrontendInput() == 'select') {
                    return $attribute->getSource()->getOptionText($attributeValue);
                }
                return $attributeValue;
            }
        }
        return '';
    }

    /**
     * Gets the product image url
     *
     * @param Mage_Sales_Model_Quote_Item | Mage_Sales_Model_Order_Item
     * @return string
     */
    public function getImage($lineItem)
    {
        return Mage::helper('bronto_common')->getProductImageUrl($this->getProduct($lineItem));
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
            return $lineItem->getQtyOrdered();
        } else {
            return $lineItem->getQty();
        }
    }

    /**
     * Gets the discount amount for the cart container
     *
     * @return float
     */
    public function getDiscountAmount()
    {
        $object = $this->getSalesObject();
        if ($object instanceof Mage_Sales_Model_Quote) {
            return $object->getSubtotal() - $object->getSubtotalWithDiscount();
        } else {
            return $object->getDiscountAmount();
        }
    }

    /**
     * Should the block even write the code?
     *
     * @return boolean
     */
    public function shouldWriteDom()
    {
        return ($this->getCartRecoveryCode() && $this->getSalesObject());
    }

    /**
     * Gets the current code for the sales object
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        if ($this->getOrder()) {
            return $this->getOrder()->getOrderCurrencyCode();
        } else {
            return $this->getQuote()->getQuoteCurrencyCode();
        }
    }
}
