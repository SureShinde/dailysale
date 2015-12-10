<?php 
class Buysafe_Siteseals_Block_Seals extends Mage_Core_Block_Template
{
    
    public function hasHash()
    {
    	 return Mage::helper('buysafe')->hasHash();
    }    
    
    public function getHash()
    {
    	 return Mage::helper('buysafe')->getHash();
    }
    
    public function isEnabled()
    {
    	 return Mage::helper('buysafe')->isEnabled();
    }
        
    public function getKickerList()
    {
    	 return Mage::helper('buysafe')->getKickerList();
    }
}