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
 * Run Export Manually button
 */
class WIC_Criteotags_Block_System_Config_Run extends Mage_Adminhtml_Block_System_Config_Form_Field 
{
	
	/**
	 * Generate html for button
	 * 
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string $html
	 * @see Mage_Adminhtml_Block_System_Config_Form_Field::_getElementHtml()
	 */
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
		$this->setElement($element);
		
		$url = $this->getUrl('adminhtml/criteotags/export');
		
		$html = $this->getLayout()->createBlock('adminhtml/widget_button')
			->setType('button')
			->setLabel($this->__('Run Export Manually'))
			->setOnClick("setLocation('$url')")
			->setId($element->getHtmlId())
			->toHtml();
		
		return $html;
	}
}