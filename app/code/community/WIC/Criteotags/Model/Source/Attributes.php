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

 
class WIC_Criteotags_Model_Source_Attributes
{
    public function toOptionArray()
    {
    	$attributes= Mage::getSingleton('criteotags/export_convert_parser_product')->getExternalAttributes();
    	array_unshift($attributes, array("value"=>"none","label"=>"Sélectionnez l'attribut à mapper"));
        return $attributes;        
    }
}
