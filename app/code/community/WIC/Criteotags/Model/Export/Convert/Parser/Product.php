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

 
class WIC_Criteotags_Model_Export_Convert_Parser_Product extends Mage_Catalog_Model_Convert_Parser_Product
{


    /**
     * Retrieve accessible external product attributes
     *
     * @return array
     */
    public function getExternalAttributes()
    {
    	$productAttributes = array();
    	if(file_exists(Mage::getModuleDir(null,'Mage_Catalog') ."Model/Resource/Eav/Mysql4/Product/Attribute/Collection"))
        	$productAttributes  = Mage::getResourceModel('catalog/product_attribute_collection')->load();
        else
        {
        	
	        $entityTypeId = Mage::getSingleton('eav/config')->getEntityType('catalog_product')->getId();
	        $productAttributes = Mage::getResourceModel('eav/entity_attribute_collection')
	            ->setEntityTypeFilter($entityTypeId)
	            ->load();
        }
        
        $attributes = $this->_externalFields;

        foreach ($productAttributes as $attr) {
            $code = $attr->getAttributeCode();
            if (in_array($code, $this->_internalFields) || $attr->getFrontendInput() == 'hidden') {
                continue;
            }
            $attributes[$code] = $code;
        }

        foreach ($this->_inventoryFields as $field) {
            $attributes[$field] = $field;
        }

        return $attributes;
    }
}
