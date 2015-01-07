<?php

class Bronto_Product_Model_Collect_Crosssell extends Bronto_Product_Model_Collect_Abstract
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
        $cross = $this->_product->getCrossSellProductCollection();
        Mage::getModel('cataloginventory/stock')->addInStockFilterToCollection($cross);
        return $this->_fillProducts($cross);
    }
}
