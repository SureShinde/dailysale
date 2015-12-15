<?php

/**
* Web In Color
*
* NOTICE OF LICENSE
*
* This source file is subject to the EULA
* that is bundled with this package in the file WIC-LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://store.webincolor.fr/WIC-LICENSE.txt
* 
* @package		WIC_Criteotags
* @copyright   Copyright (c) 2010-2014 Web In Color (http://www.webincolor.fr)
* @author		Web In Color <contact@webincolor.fr>
**/


/**
 * Admin controller
 */
class WIC_Criteotags_Adminhtml_CriteotagsController extends Mage_Adminhtml_Controller_Action {
	
	//////////////
	////////////// System Config
	//////////////
	
	/**
	 * Export Manually
	 */
	public function exportAction() {				
		
		try {
		
			Mage::getModel('criteotags/observer')->cron(null);			
			
			$url = Mage::getModel('criteotags/export_xml')->getExportFileUrl();
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('criteotags')->__('Criteo feed has been generated at the following url : <a href="%s" target="_blank">%s</a>',$url,$url));
		}
		catch(Exception $e) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('criteotags')->__('Criteo feed generation has failed with the following error message: "%s".', $e->getMessage()));
		}
		
		$this->_redirect('*/system_config/edit/section/criteotags');
	}

}