<?php

class Bronto_Product_Model_Collect_Related extends Bronto_Product_Model_Collect_Abstract
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
        $related = $this->_product->getRelatedProductCollection();
        Mage::getModel('cataloginventory/stock')->addInStockFilterToCollection($related);
        return $this->_fillProducts($related);
    }
}
