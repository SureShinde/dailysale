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
 * CriteoTags Html page block
 *
 * @category   WIC
 * @package    WIC_Criteotags
 * @author     Web in Color
 */
class WIC_Criteotags_Model_Source_Sitetype
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'d', 'label'=>Mage::helper('criteotags')->__('Classic')),
            array('value'=>'m', 'label'=>Mage::helper('criteotags')->__('Mobile')),
            array('value'=>'t', 'label'=>Mage::helper('criteotags')->__('Tablet')),
        );
    }

}