<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Relatedproducts
 * @version    1.4.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


/**
 * Related products Block
 */
class AW_Relatedproducts_Block_Relatedproducts extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Default count of products to show
     */
    const ONE_TIME_INSTALL_ORDERS_LIMIT = 3;
    /**
     * Config path to Enabled
     */
    const XML_PATH_ENABLED = 'relatedproducts/general/enabled';
    /**
     * Class to remove
     */
    const COMMUNITY_RELATED_CLASS = 'Mage_Catalog_Block_Product_List_Related';
    const TEMPLATE_EMPTY = 'catalog/product/list/empty.phtml';
    const FLAG_CHECKOUT_MODE = '_flag_checkout_mode';

    protected $_itemCollection = null;
    protected $_relatedCollection;

    protected function _getHelper($ext = '')
    {
        return Mage::helper('relatedproducts' . ($ext ? '/' . $ext : ''));
    }

    /**
     * Retrives current product id
     * @return integer|null
     */
    public function getProductIds()
    {
        /** @var $helper AW_Relatedproducts_Helper_Data */
        $helper = $this->_getHelper();
        $productIds = array();
        foreach ($this->getProducts() as $product) {
            $productId = $product->getId();
            $productIds[] = $productId;
            if (!$helper->isInstalledForProduct($productId)) {
                $helper->installForProduct($productId, null, $this->getProductsToDisplay());
            }
        }
        return $productIds;
    }

    /**
     * Retrives current category id
     * @return integer|null
     */
    public function getCategoryId()
    {
        return Mage::registry('category') ? Mage::registry('category')->getId() : null;
    }

    /**
     * Retrives block is enabled from config
     * @return boolean
     */
    public function getEnabled()
    {
        return !!Mage::getStoreConfig(self::XML_PATH_ENABLED);
    }

    public function getProducts()
    {
        $products = array();
        if ($this->isCheckoutMode()) {
            /** @var $cart Mage_Checkout_Model_Cart */
            $cart = Mage::getSingleton('checkout/cart');
            foreach ($cart->getQuote()->getItemsCollection() as $item) {
                /** @var $item Mage_Sales_Model_Quote_Item */
                $products[] = $item->getProduct();
            }
        } else if ($currentProduct = Mage::registry('product')) {
            $products[] = $currentProduct;
        }
        return $products;
    }

    public function disableRelated()
    {
        if (!$this->getEnabled() || $this->_getHelper()->getExtDisabled()) {
            return;
        }
        $deleteId = null;
        $i = 0;
        foreach ($this->getParentBlock()->_children as $child) {
            if (get_class($child) == self::COMMUNITY_RELATED_CLASS) {
                $child->setTemplate(self::TEMPLATE_EMPTY);
            }
            $i++;
        }
    }

    protected function _beforeToHtml()
    {
        $this->_prepareProductPrices();
        parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $html = '';
        if ($arpBlock = $this->getData('_aw_arp2_cs_block')) {
            $html .= $arpBlock->toHtml();
        }
        return $html . parent::_toHtml();
    }

    /**
     * Rtrives number of products to display
     * @return integer
     */
    public function getProductsToDisplay()
    {
        if (($num = $this->_getHelper('config')->getGeneralProductsToDisplay()) > 0) {
            return $num;
        } else {
            return self::ONE_TIME_INSTALL_ORDERS_LIMIT;
        }
    }

    public function getCollection()
    {
        if (!$this->_relatedCollection) {
            if ($productIds = $this->getProductIds()) {
                /** @var $relatedCollection AW_Relatedproducts_Model_Mysql4_Relatedproducts_Collection */
                $relatedCollection = Mage::getModel('relatedproducts/relatedproducts')
                    ->getCollection()
                    ->addProductFilter($productIds)
                    ->addStoreFilter();
                $this->_relatedCollection = $relatedCollection;
            } else {
                $this->_relatedCollection = new Varien_Data_Collection();
            }
        }
        return $this->_relatedCollection;
    }

    public function getUpdatedCollection()
    {
        $this->_relatedCollection = null;
        return $this->getCollection();
    }

    public function getRelatedProductsCollection()
    {
        $items = $this->getCollection();
        $relatedIds = array();

        foreach ($items as $item) {
            # actually runs only once max, for 1 collection element
            $relatedItems = unserialize($item->getData('related_array'));
            arsort($relatedItems, SORT_NUMERIC); //order by number of purchases
            foreach ($relatedItems as $key => $value) {
                array_push($relatedIds, $key);
            }
        }
        $relatedIds = array_unique($relatedIds);

        $this->_itemCollection = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->getCollection();


        Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter(
            $this->_itemCollection, Mage::getSingleton('checkout/session')->getQuoteId()
        );

        $this->_itemCollection->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes());

        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_itemCollection);
        $this->_itemCollection->addAttributeToFilter('entity_id', array('in' => $relatedIds));

        if ($this->_getHelper('config')->getGeneralSameCategory()
            && ($currentCategory = Mage::registry('current_category'))
        ) {
            $this->_itemCollection->addCategoryFilter($currentCategory);
        }

        $this->_itemCollection->setPageSize($this->getProductsToDisplay())->setCurPage(1);

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this->_returnSortedArray($this->_itemCollection, $relatedIds);
    }

    /**
     * Sort items by relevance
     * @param Mage_Catalog_Model_Mysql4_Product_Collection $collection
     * @param array $keysToSort
     * @return array
     */
    protected function _returnSortedArray($collection, $keysToSort = null)
    {
        $array = array();
        if ($keysToSort && is_array($keysToSort)) {
            foreach ($keysToSort as $keyId) {
                if ($product = $this->_getItemFromCollection($collection, $keyId)) {
                    $array[] = $product;
                }
            }
        }
        return $array;
    }

    protected function _getItemFromCollection($collection, $id)
    {
        foreach ($collection as $item) {
            if ($item->getId() == $id) {
                return $item;
            }
        }
        return null;
    }

    private function _prepareProductPrices()
    {
        $this->addPriceBlockType('bundle', 'bundle/catalog_product_price', 'bundle/catalog/product/price.phtml');
        $this->addPriceBlockType(
            'giftcard', 'enterprise_giftcard/catalog_product_price', 'giftcard/catalog/product/price.phtml'
        );
    }

    public function setCheckoutMode($flag = true)
    {
        return $this->setData(self::FLAG_CHECKOUT_MODE, $flag);
    }

    public function isCheckoutMode()
    {
        return (bool)$this->getData(self::FLAG_CHECKOUT_MODE);
    }

    public function getRequiredOptionsInCollection($collection)
    {
        foreach($collection as $product){
            if(!$this->getRequiredOptions($product)){
                return true;
            }
        }
        return false;
    }

    public function getRequiredOptions($product)
    {
        if ($product->getTypeInstance(true)->hasRequiredOptions($product)){
            return true;
        }
        return false;
    }
}