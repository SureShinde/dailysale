<?php
/**
 *
 * @category    Turnto
 * @package     Turnto_Admin
 * @copyright   
 */

/**
 * Catalog product model
 *
 *
 * @category    Turnto
 * @package     Turnto_Admin
 * @author      nanowebgroup.com
 */
class Turnto_Admin_Model_Catalog_Product extends Mage_Catalog_Model_Product
{

    /**
     * Returns rating summary
     *
     * @return mixed
     */
    public function getRatingSummary()
    {
        
        $rating = Mage::getModel('turnto_admin/rating')
                                  ->getCollection()
                                  ->addFieldToFilter('product_id', $this->getId())
                                  ->getFirstItem();

       // var_dump("prod id:".$this->getId()." review count:".$rating->getReviewCount()); 

        if($rating->getReviewCount())
             return true;
        
        return false;
    }
}
