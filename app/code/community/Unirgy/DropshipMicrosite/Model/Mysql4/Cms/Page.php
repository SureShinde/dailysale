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
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipMicrosite_Model_Mysql4_Cms_Page extends Mage_Cms_Model_Mysql4_Page
{
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);

        $vendor = Mage::helper('umicrosite')->getCurrentVendor();
        if ($vendor) {
            $select->where("udropship_vendor=? || identifier='no-route'", $vendor->getId());
        } else {
            $select->where('udropship_vendor is null');
        }
        return $select;
    }
}