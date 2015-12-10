<?php 
class Buysafe_Siteseals_Model_Kickertype
{
    public function toOptionArray()
    {
        
    	 return Mage::helper('buysafe')->getKickerList();
    }

}