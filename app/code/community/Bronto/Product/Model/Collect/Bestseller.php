<?php

class Bronto_Product_Model_Collect_Bestseller extends Bronto_Product_Model_Collect_Abstract
{
    /**
     * @see parent
     */
    public function collect()
    {
        $bestSellers = Mage::getResourceModel('reports/product_collection')
            ->addOrderedQty()
            ->setStoreId($this->getStoreId())
            ->addStoreFilter($this->getStoreId())
            ->setOrder('ordered_qty', 'desc')
            ->setPageSize($this->getRemainingCount());

        if (!empty($this->_excluded)) {
            $bestSellers->addIdFilter(array_keys($this->_excluded), true);
        }

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($bestSellers);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($bestSellers);
        Mage::getModel('cataloginventory/stock')->addInStockFilterToCollection($bestSellers);

        return $this->_fillProducts($bestSellers);
    }
}
