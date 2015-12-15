<?php

class Bronto_Product_Model_Collect_Upsell extends Bronto_Product_Model_Collect_Abstract
{
    /**
     * @see parent
     */
    public function isProductRelated()
    {
        return true;
    }

    /**
     * @see parent
     */
    public function collect()
    {
        $upsell = $this->_product->getUpSellProductCollection();
        Mage::getModel('cataloginventory/stock')->addInStockFilterToCollection($upsell);
        return $this->_fillProducts($upsell);
    }
}
