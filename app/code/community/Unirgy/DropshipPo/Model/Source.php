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

class Unirgy_DropshipPo_Model_Source extends Unirgy_Dropship_Model_Source_Abstract
{
    const UDPO_STATUS_PENDING    = 0;
    const UDPO_STATUS_EXPORTED   = 10;
    const UDPO_STATUS_RETURNED   = 11;
    const UDPO_STATUS_ACK        = 9;
    const UDPO_STATUS_BACKORDER  = 5;
    const UDPO_STATUS_ONHOLD     = 4;
    const UDPO_STATUS_READY      = 3;
    const UDPO_STATUS_PARTIAL    = 2;
    const UDPO_STATUS_SHIPPED    = 1;
    const UDPO_STATUS_CANCELED   = 6;
    const UDPO_STATUS_DELIVERED  = 7;

    const UDPO_STATUS_STOCKPO_READY   = 11;
    const UDPO_STATUS_STOCKPO_EXPORTED = 12;
    const UDPO_STATUS_STOCKPO_RECEIVED = 13;
    
    const UDPO_INCREMENT_NATIVE      = 'native';
    const UDPO_INCREMENT_ORDER_BASED = 'order_based';
    
    const SHIPMENT_INCREMENT_NATIVE      = 'native';
    const SHIPMENT_INCREMENT_ORDER_BASED = 'order_based';
    const SHIPMENT_INCREMENT_PO_BASED    = 'po_based';

    const AUTOINVOICE_SHIPMENT_NO = 0;
    const AUTOINVOICE_SHIPMENT_YES = 1;
    const AUTOINVOICE_SHIPMENT_ORDER = 2;

    public function getAllowedPoStatusesForPartialShipped()
    {
        return array(
            self::UDPO_STATUS_BACKORDER,
            self::UDPO_STATUS_ONHOLD,
            self::UDPO_STATUS_PARTIAL,
            self::UDPO_STATUS_RETURNED
        );
    }
    
    public function getAllowedPoStatusesForShipped($auto=false)
    {
        $statuses = array(
            self::UDPO_STATUS_SHIPPED,
        );
        if (!$auto) {
            $statuses[] = self::UDPO_STATUS_DELIVERED;
        }
        return $statuses;
    }
    
    public function getAllowedPoStatusesForDelivered()
    {
        return array(
            self::UDPO_STATUS_DELIVERED,
        );
    }
    
    public function getAllowedPoStatusesForCanceled()
    {
        return array(
            self::UDPO_STATUS_CANCELED,
        );
    }
    
    public function getNonSecurePoStatuses()
    {
        return array(
            self::UDPO_STATUS_PENDING,
            self::UDPO_STATUS_EXPORTED,
            self::UDPO_STATUS_ACK,
            self::UDPO_STATUS_BACKORDER,
            self::UDPO_STATUS_ONHOLD,
            self::UDPO_STATUS_READY,
            self::UDPO_STATUS_PARTIAL,
            self::UDPO_STATUS_STOCKPO_READY,
            self::UDPO_STATUS_STOCKPO_EXPORTED,
            self::UDPO_STATUS_STOCKPO_RECEIVED,
            self::UDPO_STATUS_RETURNED
        );
    }
    
    public function toOptionHash($selector=false)
    {
        $hlp = Mage::helper('udpo');

        $options = array();

        switch ($this->getPath()) {

        case 'udropship/stockpo/generate_on_po_status':
    	case 'udropship/batch/export_on_po_status':
        case 'udropship/purchase_order/default_po_status':
        case 'udropship/purchase_order/default_virtual_po_status':
        case 'udropship/vendor/restrict_udpo_status':
        case 'udropship/pocombine/notify_on_status':
        case 'udropship/pocombine/after_notify_status':
        case 'udropship/statement/statement_po_status':
        case 'batch_export_orders_export_on_po_status':
        case 'statement_po_status':
        case 'po_statuses':
        case 'notify_by_udpo_status':
        case 'initial_po_status':
        case 'initial_virtual_po_status':
        case 'vendor_po_grid_status_filter':
            $options = array(
                self::UDPO_STATUS_PENDING    => $hlp->__('Pending'),
                self::UDPO_STATUS_EXPORTED   => $hlp->__('Exported'),
                self::UDPO_STATUS_ACK        => $hlp->__('Acknowledged'),
                self::UDPO_STATUS_BACKORDER  => $hlp->__('Backorder'),
                self::UDPO_STATUS_ONHOLD     => $hlp->__('On Hold'),
                self::UDPO_STATUS_READY      => $hlp->__('Ready to Ship'),
                self::UDPO_STATUS_PARTIAL    => $hlp->__('Shipped'),
                self::UDPO_STATUS_SHIPPED    => $hlp->__('Shipped'),
                self::UDPO_STATUS_DELIVERED  => $hlp->__('Delivered'),
                self::UDPO_STATUS_CANCELED   => $hlp->__('Canceled'),
                self::UDPO_STATUS_RETURNED   => $hlp->__('Returned'),
            );
            if (Mage::helper('udropship')->isModuleActive('ustockpo')) {
                $options[self::UDPO_STATUS_STOCKPO_READY]   = $hlp->__('Ready for stock PO');
                $options[self::UDPO_STATUS_STOCKPO_EXPORTED]   = $hlp->__('Exported stock PO');
                $options[self::UDPO_STATUS_STOCKPO_RECEIVED]   = $hlp->__('Received stock PO');
            }
            if (in_array($this->getPath(), array('initial_po_status','statement_po_status','initial_virtual_po_status','batch_export_orders_export_on_po_status'))) {
                $options = array('999' => $hlp->__('* Default (global setting)')) + $options;
            }
            break;
            
        case 'udropship/purchase_order/po_increment_type':
        case 'po_increment_types':
            $options = array(
                self::UDPO_INCREMENT_NATIVE      => $hlp->__('Magento Native'),
                self::UDPO_INCREMENT_ORDER_BASED => $hlp->__('Order Based'),
            );
            break;

        case 'udropship/purchase_order/autoinvoice_shipment':
            $options = array(
                Unirgy_DropshipPo_Model_Source::AUTOINVOICE_SHIPMENT_NO => $hlp->__('No'),
                Unirgy_DropshipPo_Model_Source::AUTOINVOICE_SHIPMENT_YES => $hlp->__('Yes'),
                Unirgy_DropshipPo_Model_Source::AUTOINVOICE_SHIPMENT_ORDER => $hlp->__('Trigger whole order invoice'),
            );
            break;
        
        case 'udropship/purchase_order/shipment_increment_type':
        case 'shipment_increment_types':
            $options = array(
                self::SHIPMENT_INCREMENT_NATIVE      => $hlp->__('Magento Native'),
                self::SHIPMENT_INCREMENT_ORDER_BASED => $hlp->__('Order Based'),
                self::SHIPMENT_INCREMENT_PO_BASED    => $hlp->__('PO Based'),
            );
            break;

        case 'vendor_po_grid_sortby':
            $options = array(
                'order_increment_id' => $hlp->__('Order ID'),
                'increment_id' => $hlp->__('PO ID'),
                'order_date' => $hlp->__('Order Date'),
                'po_date' => $hlp->__('PO Date'),
                'shipping_method' => $hlp->__('Delivery Method'),
                'udropship_status' => $hlp->__('PO Status'),
            );
            break;

        case 'new_order_notifications':
            $options = array(
                '' => $hlp->__('* No notification'),
                '1' => $hlp->__('* Email notification'),
                '-1' => $hlp->__('* Email notification By Status'),
            );
            $config = Mage::getConfig()->getNode('global/udropship/notification_methods');
            foreach ($config->children() as $code=>$node) {
                if (!$node->label) {
                    continue;
                }
                $options[$code] = $hlp->__((string)$node->label);
            }
            asort($options);
            break;

        default:
            Mage::throwException($hlp->__('Invalid request for source options: '.$this->getPath()));
        }

        if ($selector) {
            $options = array(''=>$hlp->__('* Please select')) + $options;
        }

        return $options;
    }

}
