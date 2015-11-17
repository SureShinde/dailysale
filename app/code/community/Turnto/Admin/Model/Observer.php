<?php

class Turnto_Admin_Model_Observer
{
 
    public function getProductsRatingsFeed()
    {
        
        if(Mage::helper('adminhelper1')->isFeedActivated())
            	  Mage::helper('adminhelper1')->loadRatings();

        //return $this;
    }
 
 
}