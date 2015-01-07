<?php

class Bronto_Product_Model_Collect_Mostviewed extends Bronto_Product_Model_Collect_Abstract
{
    const DAYS_THRESHOLD = '30';

    /**
     * @see parent
     */
    public function collect()
    {
        $mostViewed = Mage::getResourceModel('reports/product_collection')
            ->addStoreFilter($this->getStoreId())
            ->setPageSize($this->getRemainingCount())
            ->addViewsCount(date('Y-m-d', strtotime('-' . self::DAYS_THRESHOLD . ' days')), date('Y-m-d'));

        // Add Status and visibility filters
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($mostViewed);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($mostViewed);
        Mage::getModel('cataloginventory/stock')->addInStockFilterToCollection($mostViewed);
        return $this->_fillProducts($mostViewed);
    }
}
