<?php

class Bronto_Product_Model_Collect_Mostviewed extends Bronto_Product_Model_Collect_Abstract
{
    const DAYS_THRESHOLD = '30';

    /**
     * @see parent
     */
    public function collect()
    {
        $mostViewed = Mage::getResourceModel('reports/report_product_viewed_collection')
            ->addStoreFilter(array($this->getStoreId()))
            ->setDateRange(date('Y-m-d', strtotime('-' . self::DAYS_THRESHOLD . ' days')), date('Y-m-d'))
            ->setPageSize($this->getRemainingCount());

        // Add Status and visibility filters
        return $this->_fillProducts($mostViewed);
    }
}
