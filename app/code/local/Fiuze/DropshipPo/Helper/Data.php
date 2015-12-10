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
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Fiuze_DropshipPo_Helper_Data extends Unirgy_DropshipPo_Helper_Data
{
    public function getVendorPoCollection($full = false)
    {
        if (!$this->_vendorPoCollection) {
            $vendorId = Mage::getSingleton('udropship/session')->getVendorId();
            $vendor = Mage::helper('udropship')->getVendor($vendorId);
            $collection = Mage::getModel('udpo/po')->getCollection();

            $orderTableQted = $collection->getResource()->getReadConnection()->quoteIdentifier('sales/order');
            $collection->join('sales/order', "$orderTableQted.entity_id=main_table.order_id", array(
                'order_increment_id' => 'increment_id',
                'order_created_at' => 'created_at',
                'shipping_method',
            ));

            $collection->addAttributeToFilter('udropship_vendor', $vendorId);

            $r = Mage::app()->getRequest();

            if(!$full) {
                if (($v = $r->getParam('filter_order_id_from'))) {
                    $collection->addAttributeToFilter("$orderTableQted.increment_id", array('gteq' => $v));
                }
                if (($v = $r->getParam('filter_order_id_to'))) {
                    $collection->addAttributeToFilter("$orderTableQted.increment_id", array('lteq' => $v));
                }

                if (($v = $r->getParam('filter_order_date_from'))) {
                    $_filterDate = Mage::app()->getLocale()->date();
                    $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                    $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                    $collection->addAttributeToFilter("$orderTableQted.created_at", array('gteq' => $_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
                }
                if (($v = $r->getParam('filter_order_date_to'))) {
                    $_filterDate = Mage::app()->getLocale()->date();
                    $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                    $_filterDate->addDay(1);
                    $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                    $collection->addAttributeToFilter("$orderTableQted.created_at", array('lteq' => $_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
                }

                if (($v = $r->getParam('filter_po_id_from'))) {
                    $collection->addAttributeToFilter('main_table.increment_id', array('gteq' => $v));
                }
                if (($v = $r->getParam('filter_po_id_to'))) {
                    $collection->addAttributeToFilter('main_table.increment_id', array('lteq' => $v));
                }

                if (($v = $r->getParam('filter_po_date_from'))) {
                    $_filterDate = Mage::app()->getLocale()->date();
                    $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                    $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                    $collection->addAttributeToFilter('main_table.created_at', array('gteq' => $_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
                }
                if (($v = $r->getParam('filter_po_date_to'))) {
                    $_filterDate = Mage::app()->getLocale()->date();
                    $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                    $_filterDate->addDay(1);
                    $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                    $collection->addAttributeToFilter('main_table.created_at', array('lteq' => $_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
                }

                if (($v = $r->getParam('filter_method'))) {
                    if (array_key_exists('VIRTUAL_PO', $v)) {
                        $collection->addFieldToFilter(
                            array('main_table.udropship_method', 'main_table.is_virtual'),
                            array(array('in' => array_keys($v)), '1')
                        );
                    } else {
                        $collection->addAttributeToFilter('main_table.udropship_method', array('in' => array_keys($v)));
                    }
                }

                if (!$r->getParam('apply_filter') && $vendor->getData('vendor_po_grid_status_filter')) {
                    $filterStatuses = $vendor->getData('vendor_po_grid_status_filter');
                    $filterStatuses = array_combine($filterStatuses, array_fill(0, count($filterStatuses), 1));
                    $r->setParam('filter_status', $filterStatuses);
                }

                if (($v = $r->getParam('filter_status'))) {
                    $collection->addAttributeToFilter('main_table.udropship_status', array('in' => array_keys($v)));
                }
            }
                if (!$r->getParam('sort_by') && $vendor->getData('vendor_po_grid_sortby')) {
                    $r->setParam('sort_by', $vendor->getData('vendor_po_grid_sortby'));
                    $r->setParam('sort_dir', $vendor->getData('vendor_po_grid_sortdir'));
                }
                if (($v = $r->getParam('sort_by'))) {
                    $map = array('order_date' => 'order_created_at', 'po_date' => 'created_at');
                    if (isset($map[$v])) {
                        $v = $map[$v];
                    }
                    $collection->setOrder($v, $r->getParam('sort_dir'));
                }

            $this->_vendorPoCollection = $collection;
        }
        return $this->_vendorPoCollection;
    }
}
