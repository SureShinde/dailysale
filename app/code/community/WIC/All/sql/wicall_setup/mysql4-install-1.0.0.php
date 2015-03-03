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
$this->startSetup();

$feedData = array();
$feedData[] = array(
    'severity'      => 4,
    'date_added'    => gmdate('Y-m-d H:i:s', time()),
    'title'         => 'Your Web in Color extension has been installed. Remember to flush all cache, recompile, log-out and log back in.',
    'description'   => 'You can see versions of the installed extensions right in the admin.',
    'url'           => 'http://store.webincolor.fr'
);

Mage::getModel('adminnotification/inbox')->parse($feedData);

$this->endSetup();