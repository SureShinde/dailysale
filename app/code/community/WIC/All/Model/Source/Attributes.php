<?php

class WIC_All_Model_Source_Attributes
{
    public function toOptionArray()
    {
    	$attributes= Mage::getSingleton('wicall/catalog_convert_parser_product')->getExternalAttributes();
    	array_unshift($attributes, array("value"=>"none","label"=> Mage::helper('wicall')->__("Select attribute to map")));
        return $attributes;        
    }
}
