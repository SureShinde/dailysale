<?php

class IWD_Signin_Model_System_Block
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
    	$blocks = array();
    	$collection = $collection = Mage::getModel('cms/block')->getCollection()->addFieldToFilter('is_active',array('eq'=>1));
    	$blocks[] = array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--'));
    	foreach($collection as $block){
    		$blocks[] = array('value' => $block->getId(), 'label'=>$block->getTitle());
    	}
    	
    	
    	
        return $blocks;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
    	$blocks = array();
    	$collection = $collection = Mage::getModel('cms/block')->getCollection()->addFieldToFilter('is_active',array('eq'=>1));
    	 
    	foreach($collection as $block){
    		$blocks[$block->getId()] = $block->getTitle();
    	}
    	 
    	 
    	 
    	return $blocks;
    	
    	
       
    }

}
