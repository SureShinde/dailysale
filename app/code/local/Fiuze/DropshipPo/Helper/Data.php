<?php
/**
 *
 * @author Fiuze Team
 * @category Fiuze
 * @package Fiuze_DropshipPo
 * @copyright Copyright (c) 2016 Fiuze
 */

require_once Mage::getBaseDir('lib').DS.'SweetTooth/pest/vendor/autoload.php';

class Fiuze_DropshipPo_Helper_Data extends Unirgy_DropshipPo_Helper_Data
{
    public function getVendorPoCollection($full = false)
    {
        if (!$this->_vendorPoCollection) {
            $vendorId = Mage::getSingleton('udropship/session')->getVendorId();
            $vendor = Mage::helper('udropship')->getVendor($vendorId);
            $collection = Mage::getModel('udpo/po')->getCollection();

            $orderTableQted = $collection->getResource()->getReadConnection()->quoteIdentifier('sales/order');
            $orderTable = $collection->getResource()->getReadConnection()->getTableName('sales/order');
            $collection->join('sales/order', "$orderTableQted.entity_id=main_table.order_id", array(
                'order_increment_id' => 'increment_id',
                'order_created_at' => 'created_at',
                'shipping_method',
            ));

            $collection->addAttributeToFilter('udropship_vendor', $vendorId);

            $r = Mage::app()->getRequest();

            if (!$full) {
                if (($v = $r->getParam('filter_order_id_from'))) {
                    $collection->addAttributeToFilter("$orderTable.increment_id", array('gteq' => $v));
                }
                if (($v = $r->getParam('filter_order_id_to'))) {
                    $collection->addAttributeToFilter("$orderTable.increment_id", array('lteq' => $v));
                }

                if (($v = $r->getParam('filter_order_date_from'))) {
                    $_filterDate = Mage::app()->getLocale()->date();
                    $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                    $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                    $collection->addAttributeToFilter("$orderTable.created_at",
                        array('gteq' => $_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT))
                    );
                }
                if (($v = $r->getParam('filter_order_date_to'))) {
                    $_filterDate = Mage::app()->getLocale()->date();
                    $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                    $_filterDate->addDay(1);
                    $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                    $collection->addAttributeToFilter("$orderTable.created_at",
                        array('lteq' => $_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT))
                    );
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
                    $collection->addAttributeToFilter('main_table.created_at',
                        array('gteq' => $_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT))
                    );
                }
                if (($v = $r->getParam('filter_po_date_to'))) {
                    $_filterDate = Mage::app()->getLocale()->date();
                    $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                    $_filterDate->addDay(1);
                    $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                    $collection->addAttributeToFilter('main_table.created_at',
                        array('lteq' => $_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT))
                    );
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

    public $createReturnAllShipments=false;
    public function createShipmentFromPo($udpo, $qtys=array(), $save=true, $setQtyShippedFlag=true, $noInvoiceFlag=false)
    {
        if (!Mage::helper('udropship')->isActive($udpo->getOrder()->getStore())) {
            return false;
        }

        $order = $udpo->getOrder();
        $hlp = Mage::helper('udropship');
        $hlpd = Mage::helper('udropship/protected');
        $convertor = Mage::getModel('sales/convert_order');
        $enableVirtual = Mage::getStoreConfig('udropship/misc/enable_virtual', $order->getStoreId());

        $shippingMethod = Mage::helper('udropship')->explodeOrderShippingMethod($order);

        $items = $udpo->getAllItems();

        $orderToPoItemMap = array();
        foreach ($items as $poItem) {
            $orderToPoItemMap[$poItem->getOrderItemId()] = $poItem;
        }

        $shipmentIncrement = Mage::getStoreConfig('udropship/purchase_order/shipment_increment_type', $order->getStoreId());

        if ($shipmentIncrement == Unirgy_DropshipPo_Model_Source::SHIPMENT_INCREMENT_ORDER_BASED) {
            $shipmentIncrementBase = $order->getIncrementId();
            $shipmentIndex = $order->getShipmentsCollection()->count();
        } elseif ($shipmentIncrement == Unirgy_DropshipPo_Model_Source::SHIPMENT_INCREMENT_PO_BASED) {
            $shipmentIncrementBase = $udpo->getIncrementId();
            $shipmentIndex = $udpo->getShipmentsCollection()->count();
        }

        $orderToShipItemMap = array();

        $shipments = array();
        $canShipItemFlags = array();
        foreach ($items as $poItem) {
            $orderItem = $poItem->getOrderItem();
            $canShipItemFlags[$poItem->getId()] = $this->_canShipItem($orderItem, $poItem, $orderToPoItemMap, $qtys);
        }
        foreach ($items as $poItem) {
            $orderItem = $poItem->getOrderItem();

            if (!$canShipItemFlags[$poItem->getId()]) {
                continue;
            }

            $vId = $udpo->getUdropshipVendor();
            $vendor = $hlp->getVendor($vId);

            $vIds = array();
            if ($orderItem->getHasChildren()) {
                $children = $orderItem->getChildrenItems() ? $orderItem->getChildrenItems() : $orderItem->getChildren();
                foreach ($children as $child) {
                    if (!isset($orderToPoItemMap[$child->getId()]) || !$canShipItemFlags[$orderToPoItemMap[$child->getId()]->getId()]) continue;
                    $udpoKey = $vId;
                    if (!$udpo->getUdpoNoSplitPoFlag()) {
                        if (Mage::helper('udropship')->isSeparateShipment($child, $vId) && $orderItem->isShipSeparately()) {
                            $udpoKey .= '-'.($child->getUdpoSeqNumber() ? $child->getUdpoSeqNumber() : $child->getId());
                        } elseif (Mage::helper('udropship')->isSeparateShipment($orderItem, $vId)) {
                            $udpoKey .= '-'.($orderItem->getUdpoSeqNumber() ? $orderItem->getUdpoSeqNumber() : $orderItem->getId());
                        }}
                    $vIds[$udpoKey] = $vId;
                }
                if (empty($vIds)) {
                    $udpoKey = $vId;
                    $vIds[$udpoKey] = $vId;
                }
            } else {
                $udpoKey = $vId;
                $oiParent = $orderItem->getParentItem();
                if (!$udpo->getUdpoNoSplitPoFlag()) {
                    if (Mage::helper('udropship')->isSeparateShipment($orderItem, $vId)
                        && (!$oiParent || $oiParent->isShipSeparately())
                    ) {
                        $udpoKey .= '-'.($orderItem->getUdpoSeqNumber() ? $orderItem->getUdpoSeqNumber() : $orderItem->getId());
                    } elseif ($oiParent && Mage::helper('udropship')->isSeparateShipment($oiParent, $vId)) {
                        $udpoKey .= '-'.($oiParent->getUdpoSeqNumber() ? $oiParent->getUdpoSeqNumber() : $oiParent->getId());
                    }}
                $vIds[$udpoKey] = $vId;
            }

            foreach ($vIds as $udpoKey=>$vId) {
                $vendor = $hlp->getVendor($vId);

                if (empty($shipments[$udpoKey])) {
                    $shipmentStatus = (int)Mage::getStoreConfig('udropship/vendor/default_shipment_status', $order->getStoreId());
                    if ('999' != $vendor->getData('initial_shipment_status')) {
                        $shipmentStatus = $vendor->getData('initial_shipment_status');
                    }
                    $shipments[$udpoKey] = $convertor->toShipment($order)
                        ->setUdpo($udpo)
                        ->setUdpoId($udpo->getId())
                        ->setUdpoIncrementId($udpo->getIncrementId())
                        ->setUdropshipVendor($vId)
                        ->setUdropshipStatus($shipmentStatus)
                        ->setTotalQty(0)
                        ->setShippingAmount(0)
                        ->setBaseShippingAmount(0)
                        ->setShippingAmountIncl(0)
                        ->setBaseShippingAmountIncl(0)
                        ->setShippingTax(0)
                        ->setBaseShippingTax(0);

                    if ($shipmentIncrement == Unirgy_DropshipPo_Model_Source::SHIPMENT_INCREMENT_ORDER_BASED
                        || $shipmentIncrement == Unirgy_DropshipPo_Model_Source::SHIPMENT_INCREMENT_PO_BASED
                    ) {
                        $shipmentIndex++;
                        $shipments[$udpoKey]->setIncrementId(sprintf('%s-%s', $shipmentIncrementBase, $shipmentIndex));
                    }

                    $_orderRate = $udpo->getOrder()->getBaseToOrderRate() > 0 ? $udpo->getOrder()->getBaseToOrderRate() : 1;
                    $_baseSa = $udpo->hasShipmentShippingAmount() ? $udpo->getShipmentShippingAmount() : $udpo->getBaseShippingAmountLeft();
                    $_sa = Mage::app()->getStore()->roundPrice($_orderRate*$_baseSa);
                    $shipments[$udpoKey]
                        ->setShippingAmount($_sa)
                        ->setBaseShippingAmount($_baseSa)
                        ->setShippingAmountIncl($udpo->getShippingAmountIncl())
                        ->setBaseShippingAmountIncl($udpo->getBaseShippingAmountIncl())
                        ->setShippingTax($udpo->getShippingTax())
                        ->setBaseShippingTax($udpo->getBaseShippingTax())
                        ->setUdropshipMethod($udpo->getUdropshipMethod())
                        ->setUdropshipMethodDescription($udpo->getUdropshipMethodDescription())
                    ;
                }
                if ($orderItem->isDummy(true)) {
                    if ($orderItem->getParentItem()) {
                        $qty = $orderItem->getQtyOrdered()/$orderItem->getParentItem()->getQtyOrdered();
                    } else {
                        $qty = 1;
                    }
                } else {
                    if (isset($qtys[$poItem->getId()])) {
                        $qty = $qtys[$poItem->getId()];
                    } else {
                        $qty = $poItem->getQtyToShip();
                    }
                }

                $item = $convertor->itemToShipmentItem($orderItem)->setUdpoItem($poItem)->setUdpoItemId($poItem->getId());

                $orderToShipItemMap[$orderItem->getId().'-'.$vId] = $item;

                $this->setShipmentItemQty($item, $poItem, $qty);

                if (!$orderItem->getHasChildren()
                    || $orderItem->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
                ) {
                    if (abs($orderItem->getBaseCost())<0.001) {
                        $item->setBaseCost($orderItem->getBasePrice());
                    } else {
                        $item->setBaseCost($orderItem->getBaseCost());
                    }
                }

                //$item->register();
                if ($setQtyShippedFlag) {
                    $poItem->setQtyShipped(
                        $poItem->getQtyShipped()+$item->getQty()
                    );
                    $orderItem->setQtyShipped(
                        $orderItem->getQtyShipped()+$item->getQty()
                    );
                }

                $_totQty = $item->getQty();
                if (($_parentItem = $orderItem->getParentItem())
                    && isset($orderToShipItemMap[$_parentItem->getId().'-'.$vId])
                ) {
                    $_totQty *= $orderToShipItemMap[$_parentItem->getId().'-'.$vId]->getQty();
                }

                $shipments[$udpoKey]->addItem($item);
                if (!$orderItem->isDummy(true)) {
                    $qtyOrdered = $orderItem->getQtyOrdered();
                    $_rowDivider = $_totQty/($qtyOrdered>0 ? $qtyOrdered : 1);
                    $iTax = $orderItem->getBaseTaxAmount()*($_rowDivider>0 ? $_rowDivider : 1);
                    $iDiscount = $orderItem->getBaseDiscountAmount()*($_rowDivider>0 ? $_rowDivider : 1);
                    $shipments[$udpoKey]
                        ->setBaseTaxAmount($shipments[$udpoKey]->getBaseTaxAmount()+$iTax)
                        ->setBaseDiscountAmount($shipments[$udpoKey]->getBaseDiscountAmount()+$iDiscount)
                        ->setBaseTotalValue($shipments[$udpoKey]->getBaseTotalValue()+$orderItem->getBasePrice()*$_totQty)
                        ->setTotalValue($shipments[$udpoKey]->getTotalValue()+$orderItem->getPrice()*$_totQty)
                        ->setTotalQty($shipments[$udpoKey]->getTotalQty()+$qty)
                    ;
                }
                if ($orderItem->getParentItem()) {
                    $weightType = $orderItem->getParentItem()->getProductOptionByCode('weight_type');
                    if (null !== $weightType && !$weightType) {
                        $shipments[$udpoKey]->setTotalWeight($shipments[$udpoKey]->getTotalWeight()+$orderItem->getWeight()*$_totQty);
                    }
                } else {
                    $weightType = $orderItem->getProductOptionByCode('weight_type');
                    if (null === $weightType || $weightType) {
                        $shipments[$udpoKey]->setTotalWeight($shipments[$udpoKey]->getTotalWeight()+$orderItem->getWeight()*$_totQty);
                    }
                }
                if (!$orderItem->getHasChildren()) {
                    $shipments[$udpoKey]->setTotalCost(
                        $shipments[$udpoKey]->getTotalCost()+$item->getBaseCost()*$_totQty
                    );
                }
                $shipments[$udpoKey]->setCommissionPercent($vendor->getCommissionPercent());
                $shipments[$udpoKey]->setTransactionFee($vendor->getTransactionFee());
            }
        }

        if (!$save) {
            reset($shipments);

            return count($shipments)>0 ? ($this->createReturnAllShipments ? $shipments : current($shipments)) : false;
        }

        if (empty($shipments)) return false;

        $trackingNumber = Mage::app()->getRequest()->getParam('tracking_id');
        if (!$trackingNumber) {
            $trackingNumber = end(explode(';', Mage::app()->getRequest()->getParam('import_orders_textarea')));
        }

        Mage::dispatchEvent('udpo_po_shipment_save_before', array('order'=>$order, 'udpo'=>$udpo, 'shipments'=>$shipments));

        $tracks = Mage::getModel('track/track')
            ->getCollection()
            ->addFieldToFilter('tracking_number', array('eq' => $trackingNumber))
            ->getItems();
        $trackId = reset($tracks);
        $aftershipStatus = '';

        if (!empty($trackId)) {
            $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');
            $trackings = new AfterShip\Trackings($api_key);
            $trackingId = $trackId->getTrackingId();
            $responseJson = $trackings->get_by_id($trackingId);
            $aftershipStatus = $responseJson['data']['tracking']['tag'];
        }

        if ($aftershipStatus != 'Pending' && $aftershipStatus != 'Info Received' && $aftershipStatus != 'Expired' && $aftershipStatus != '') {
            $udpoSplitWeights = array();
            foreach ($shipments as $_vUdpoKey => $_vUdpo) {
                if (empty($udpoSplitWeights[$_vUdpo->getUdropshipVendor() . '-'])) {
                    $udpoSplitWeights[$_vUdpo->getUdropshipVendor() . '-']['weights'] = array();
                    $udpoSplitWeights[$_vUdpo->getUdropshipVendor() . '-']['total_weight'] = 0;
                }
                $weight = $_vUdpo->getTotalWeight() > 0 ? $_vUdpo->getTotalWeight() : .001;
                $udpoSplitWeights[$_vUdpo->getUdropshipVendor() . '-']['weights'][$_vUdpoKey] = $weight;
                $udpoSplitWeights[$_vUdpo->getUdropshipVendor() . '-']['total_weight'] += $weight;
            }

            $transaction = Mage::getModel('core/resource_transaction');
            foreach ($shipments as $shipment) {
                Mage::helper('udropship')->addVendorSkus($shipment);
                $shipment->setNoInvoiceFlag($noInvoiceFlag);
                if (empty($udpoNoSplitWeights[$shipment->getUdropshipVendor() . '-'])
                    && !empty($udpoSplitWeights[$shipment->getUdropshipVendor() . '-']['weights'][$udpoKey])
                    && count($udpoSplitWeights[$shipment->getUdropshipVendor() . '-']['weights']) > 1
                ) {
                    $_splitWeight = $udpoSplitWeights[$shipment->getUdropshipVendor() . '-']['weights'][$udpoKey];
                    $_totalWeight = $udpoSplitWeights[$shipment->getUdropshipVendor() . '-']['total_weight'];
                    $shipment->setShippingAmount($shipment->getShippingAmount() * $_splitWeight / $_totalWeight);
                    $shipment->setBaseShippingAmount($shipment->getBaseShippingAmount() * $_splitWeight / $_totalWeight);
                    $shipment->setShippingAmountIncl($shipment->getShippingAmountIncl() * $_splitWeight / $_totalWeight);
                    $shipment->setBaseShippingAmountIncl($shipment->getBaseShippingAmountIncl() * $_splitWeight / $_totalWeight);
                    $shipment->setShippingTax($shipment->getShippingTax() * $_splitWeight / $_totalWeight);
                    $shipment->setBaseShippingTax($shipment->getBaseShippingTax() * $_splitWeight / $_totalWeight);
                }
                $order->getShipmentsCollection()->addItem($shipment);
                $udpo->getShipmentsCollection()->addItem($shipment);
                $transaction->addObject($shipment);
            }
            $transaction->addObject($order->setIsInProcess(true))->addObject($udpo->setData('___dummy', 1))->save();

            $shipped = count($shipments);
            foreach ($shipments as $shipment) {
                if (!in_array($shipment->getUdropshipStatus(), array(Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_SHIPPED, Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED))) {
                    $shipped = false;
                    break;
                }
            }
        }
        //        if ($shipped) {
        //            foreach ($shipments as $shipment) {
        //                $this->completeUdpoIfShipped($shipment, true);
        //                break;
        //            }
        //        } else {
        //            $this->processPoStatusSave($udpo, Unirgy_DropshipPo_Model_Source::UDPO_STATUS_READY, true);
        //        }

        Mage::dispatchEvent('udpo_po_shipment_save_after', array('order'=>$order, 'udpo'=>$udpo, 'shipments'=>$shipments));

        /* no need to send notification because shipments created by vendor
        // send vendor notifications
        foreach ($shipments as $shipment) {
            $hlp->sendVendorNotification($shipment);
        }

        $hlp->processQueue();
        */

        reset($shipments);

        return count($shipments)>0 ? ($this->createReturnAllShipments ? $shipments : current($shipments)) : false;
    }
}
