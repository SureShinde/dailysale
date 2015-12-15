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
* @package		WIC_All
* @copyright   Copyright (c) 2010-2014 Web In Color (http://www.webincolor.fr)
* @author		Web In Color <contact@webincolor.fr>
**/
 
class WIC_All_Block_Extensions extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
	protected $_dummyElement;
	protected $_fieldRenderer;
	protected $_values;

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
		$html = $this->_getHeaderHtml($element);
		$modules = array_keys((array)Mage::getConfig()->getNode('modules')->children());
		sort($modules);

        foreach ($modules as $moduleName) {
        	if (strstr($moduleName, 'WIC_') === false) {
        		continue;
        	}
			
			if ($moduleName == 'WIC_All'){
				continue;
			}
			
        	$html.= $this->_getFieldHtml($element, $moduleName);
        }
        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    protected function _getFieldRenderer()
    {
    	if (empty($this->_fieldRenderer)) {
    		$this->_fieldRenderer = Mage::getBlockSingleton('adminhtml/system_config_form_field');
    	}
    	return $this->_fieldRenderer;
    }

	protected function _getFieldHtml($fieldset, $moduleCode)
    {
		$currentVer = Mage::getConfig()->getModuleConfig($moduleCode)->version;
		if (!$currentVer)
            return '';
		  
		$moduleName = substr($moduleCode, strpos($moduleCode, '_') + 1);		
            
        $status = '<a  target="_blank"><img src="'.$this->getSkinUrl('wic/all/images/ok.gif').'" title="'.$this->__("Installed").'"/></a>';		        

        $moduleName = $status . ' ' . $moduleName;
	
		$field = $fieldset->addField($moduleCode, 'label', array(
            'name'  => 'dummy',
            'label' => $moduleName,
            'value' => $currentVer,
		))->setRenderer($this->_getFieldRenderer());
			
		return $field->toHtml();
    }
    
}