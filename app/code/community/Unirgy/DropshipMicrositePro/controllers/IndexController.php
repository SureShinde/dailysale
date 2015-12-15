<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipMicrositePro
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipMicrositePro_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $vendor = Mage::helper('umicrosite')->getCurrentVendor();
        if ($vendor) {
            $this->_forward('landingPage');
            return;
        }
        $this->_forward('index', 'index', 'cms');
    }
    public function landingPageAction()
    {
        $vendor = Mage::helper('umicrosite')->getCurrentVendor();
        if ($vendor) {
            $defPageId = Mage::getStoreConfig('web/default/umicrosite_default_landingpage');
            $vPageId = $vendor->getVendorLandingPage();
            if (!Mage::helper('cms/page')->renderPage($this, $vPageId)) {
                if (!Mage::helper('cms/page')->renderPage($this, $defPageId)) {
                    $this->_forward('default', 'index', 'umicrosite');
                }
            }
            return;
        }
        $this->_forward('index', 'index', 'cms');
    }
}