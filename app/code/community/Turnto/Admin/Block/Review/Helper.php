<?php
/**
 *
 * @category    Turnto
 * @package     Turnto_Admin
 * @copyright   
 * @license     
 */

/**
 * Review helper
 *
 * @category    Turnto
 * @package     Turnto_Admin
 * @author      nanowebgroup.com
 */
class Turnto_Admin_Block_Review_Helper extends Mage_Review_Block_Helper
{
    
    protected $_turnto_rating = null;

    public function getRatingSummary()
    {
     // if(Mage::getStoreConfig('turnto_admin/feedconfig/enabled')){
            
          
               $rating = $this->_getTurntoRating();
               $rating_value = is_numeric($rating->getRating()) && (double)$rating->getRating() > 0 ? $rating->getRating() : 0;
               
               return $rating_value*20;

        // }else{
       //     return parent::getRatingSummary();
        // }

   }

    public function getReviewsCount()
    {
        
    
        // if(Mage::getStoreConfig('turnto_admin/feedconfig/enabled')){

           
              $rating = $this->_getTurntoRating();
           
              if($rating->getReviewCount()){
                 
                  return $rating->getReviewCount();
              }else{
                 
                  return 0;
              }

       //  }else{
       //    return parent::getReviewsCount();
        // }
    }

    protected function _getTurntoRating()
    {
         
         return Mage::getModel('turnto_admin/rating')
                                  ->getCollection()
                                  ->addFieldToFilter('product_id', $this->getProduct()->getId())
                                  ->getFirstItem();
         
        
    }

}
