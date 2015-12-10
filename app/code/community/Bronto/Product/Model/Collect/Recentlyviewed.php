<?php

class Bronto_Product_Model_Collect_Recentlyviewed extends Bronto_Product_Model_Collect_Abstract
{
    /**
     * @see parent
     */
    public function collect()
    {
        // It's possible that we don't have a customer
        $customer = $this->_recommendation->getCustomer();
        if (!$customer) {
            return array();
        }
        return $this->_fillProducts(Mage::getModel('reports/event')
            ->getCollection()
            ->addStoreFilter(array($this->getStoreId()))
            ->addRecentlyFiler(Mage_Reports_Model_Event::EVENT_PRODUCT_VIEW, $customer->getId())
            ->addOrder('logged_at', 'desc')
            ->setPageSize($this->getRemainingCount()));
    }
}
