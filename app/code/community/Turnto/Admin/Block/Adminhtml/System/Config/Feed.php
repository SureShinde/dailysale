<?php

class Turnto_Admin_Block_Adminhtml_System_Config_Feed
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    

   //protected $_template = 'turnto/adminhtml/system/config/feed.phtml';
   protected $_activated = null;

   public function __construct() 
   {

      parent::__construct();
      $this->setTemplate('turnto/adminhtml/system/config/feed.phtml');
       // don't load the process the entire feed on config page load.
//      $this->_activated = Mage::helper('adminhelper1')
//                              ->isFeedActivated();
//
//      if($this->_activated)
//      	  Mage::helper('adminhelper1')->loadRatings();
      

   }

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
    	
        return $this->toHtml();
    }


   public function isFeedActivated()
   {
      return $this->_activated;
   }
  
}