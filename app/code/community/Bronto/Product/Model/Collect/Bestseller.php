<?php

class Bronto_Product_Model_Collect_Bestseller extends Bronto_Product_Model_Collect_Abstract
{
    const DAYS_THRESHOLD = '30';

    /**
     * @see parent
     */
    public function collect()
    {
        $bestSellers = Mage::getResourceModel('sales/report_bestsellers_collection')
            ->setPeriod('day')
            ->addStoreFilter(array($this->getStoreId()))
            ->setDateRange(date('Y-m-d', strtotime('-' . self::DAYS_THRESHOLD . 'days')), date('Y-m-d'))
            ->setPageSize($this->getRemainingCount());

        if (!empty($this->_excluded)) {
            $bestSellers->addFieldToFilter('product_id', array('nin' => array_keys($this->_excluded)));
        }

        return $this->_fillProducts($bestSellers);
    }
}
