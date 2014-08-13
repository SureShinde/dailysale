<?php
class IWD_Signin_Block_Cms extends Mage_Core_Block_Template
{
	
	const XML_PATH_LOGO = 'signin/design/marker';
	const XML_PATH_BLOCK = 'signin/design/blockid';
	
	public function getLogo()
	{
		$isSecure = Mage::app()->getStore()->isCurrentlySecure();
		
		$logo = Mage::getStoreConfig(self::XML_PATH_LOGO);
		if (empty($logo)){
			return false;
		}

	
		
		$logo = Mage::getBaseUrl('media', $isSecure)   . 'signin/' . $logo;
		return $logo;
	}
	
	
	public function getCmsBlock(){
		$blockId = Mage::getStoreConfig(self::XML_PATH_BLOCK);
		$block = Mage::getModel('cms/block')->load($blockId);
		
		$helper = Mage::helper('cms');
		$processor = $helper->getBlockTemplateProcessor();
		$html = $processor->filter($block->getContent());
		
		return $html;
	}
}