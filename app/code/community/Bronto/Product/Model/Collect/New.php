<?php

class Bronto_Product_Model_Collect_New extends Bronto_Product_Model_Collect_Abstract
{
    /**
     * @see parent
     */
    public function collect()
    {
        $todayDate = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
        $newProducts = Mage::getResourceModel('catalog/product_collection')
            ->addStoreFilter($this->getStoreId())
            ->addAttributeToFilter('news_from_date', array('or'=> array(
                 0 => array('date' => true, 'to' => $todayDate),
                 1 => array('is' => new Zend_Db_Expr('null')))
             ), 'left')
             ->addAttributeToFilter('news_to_date', array('or'=> array(
                 0 => array('date' => true, 'from' => $todayDate),
                 1 => array('is' => new Zend_Db_Expr('null')))
             ), 'left')
             ->addAttributeToFilter(
                 array(
                     array('attribute' => 'news_from_date', 'is'=>new Zend_Db_Expr('not null')),
                     array('attribute' => 'news_to_date', 'is'=>new Zend_Db_Expr('not null'))
                     )
               )
             ->addAttributeToSort('news_from_date', 'desc')
             ->setPageSize($this->getRemainingCount());

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($newProducts);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($newProducts);
        Mage::getModel('cataloginventory/stock')->addInStockFilterToCollection($newProducts);
        return $this->_fillProducts($newProducts);
    }
}
