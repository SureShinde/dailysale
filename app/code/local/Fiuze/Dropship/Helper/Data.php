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
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
require_once Mage::getBaseDir('lib').DS.'SweetTooth/pest/vendor/autoload.php';
class Fiuze_Dropship_Helper_Data extends Unirgy_Dropship_Helper_Data
{
    /**
     * Process tracking status update
     *
     * Will process only tracks with TRACK_STATUS_READY status
     *
     * @param Mage_Sales_Model_Order_Shipment_Track $track
     * @param boolean|Mage_Core_Model_Resource_Transaction $save
     * @param null|boolean $complete
     */
    public function processTrackStatus($track, $save=false, $complete=null)
    {
        if (is_array($track)) {
            $tracks = $track;
            reset($tracks);
            $track = current($track);
        } else {
            $tracks = array($track);
        }
        $shipment = $track->getShipment();

        $order = $shipment->getOrder();
        $storeId = $order->getStoreId();

        $saveShipment = false;
        $saveOrder = false; //not used yet

        $notifyTracks = array();

        foreach ($tracks as $track) {
            $saveTrack = false;

            // is the track ready to be marked as shipped
            $trackReady = $track->getUdropshipStatus()===Unirgy_Dropship_Model_Source::TRACK_STATUS_READY;
            // is the track shipped
            $shipped = $track->getUdropshipStatus()==Unirgy_Dropship_Model_Source::TRACK_STATUS_SHIPPED;
            // is the track delivered
            $delivered = $track->getUdropshipStatus()===Unirgy_Dropship_Model_Source::TRACK_STATUS_DELIVERED;

            // actions that need to be done if the track is not marked as shipped yet
            if (!$shipped) {
                // if new track record, set initial values
                if (!$track->getUdropshipStatus()) {
                    $vendorId = $shipment->getUdropshipVendor();
                    $pollTracking = Mage::getStoreConfig('udropship/customer/poll_tracking', $storeId);
                    $trackApi = Mage::helper('udropship')->getVendor($vendorId)->getTrackApi();
                    if ($pollTracking && $trackApi) {
                        $track->setUdropshipStatus(Unirgy_Dropship_Model_Source::TRACK_STATUS_PENDING);
                        $repeatIn = Mage::getStoreConfig('udropship/customer/repeat_poll_tracking', $track->getShipment()->getOrder()->getStoreId());
                        if ($repeatIn<=0) {
                            $repeatIn = 12;
                        }
                        $repeatIn = $repeatIn*60*60;
                        $track->setNextCheck(date('Y-m-d H:i:s', time()+$repeatIn));
                    } else {
                        $track->setUdropshipStatus(Unirgy_Dropship_Model_Source::TRACK_STATUS_READY);
                    }
                    $saveTrack = true;
                }
                if ($track->getUdropshipStatus()==Unirgy_Dropship_Model_Source::TRACK_STATUS_READY) {
                    $track->setUdropshipStatus(Unirgy_Dropship_Model_Source::TRACK_STATUS_SHIPPED);
                    $notifyTracks[] = $track;
                    $saveTrack = true;
                }
                if ($delivered) {
                    $saveTrack = true;
                }
                if ($saveTrack) {
                    $this->_processTrackStatusSave($save, $track);
                }
            }
        }

        if (!empty($notifyTracks)) {
            $notifyOnOld = Mage::getStoreConfig('udropship/customer/notify_on', $storeId);
            $notifyOn = Mage::getStoreConfig('udropship/customer/notify_on_tracking', $storeId);
            if ($notifyOn) {
                $this->sendTrackingNotificationEmail($notifyTracks);
                $shipment->setEmailSent(true);
                $saveShipment = true;
            } elseif ($notifyOnOld==Unirgy_Dropship_Model_Source::NOTIFYON_TRACK) {
                $shipment->sendEmail();
                $shipment->setEmailSent(true);
                $saveShipment = true;
            }
        }

        $delivered = false;
        if ($shipment->getUdropshipStatus()!=Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED) {
            $nonDeliveredTracks = Mage::getModel('sales/order_shipment_track')->getCollection()
                ->setShipmentFilter($shipment->getId())
                ->addAttributeToFilter('udropship_status', array('nin'=>array(Unirgy_Dropship_Model_Source::TRACK_STATUS_DELIVERED)))
            ;
            $deliveredTracks = Mage::getModel('sales/order_shipment_track')->getCollection()
                ->setShipmentFilter($shipment->getId())
                ->addAttributeToFilter('udropship_status', array('in'=>array(Unirgy_Dropship_Model_Source::TRACK_STATUS_DELIVERED)))
            ;
            if (!$nonDeliveredTracks->count() && $deliveredTracks->count()) {
                $delivered = true;
            }
        }

        if ($shipment->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_SHIPPED || $shipment->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED) {
            if ($delivered && $shipment->getUdropshipStatus()!=Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED) {
                $this->processShipmentStatusSave(
                    $shipment, Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED);
                $this->completeUdpoIfShipped($shipment, true);
            }
            return;
        }

        if (is_null($complete)) {
            if (Mage::getStoreConfigFlag('udropship/vendor/auto_shipment_complete', $storeId)) {
                switch (Mage::getStoreConfigFlag('udropship/vendor/auto_shipment_complete', $storeId)) {
                    case Unirgy_Dropship_Model_Source::AUTO_SHIPMENT_COMPLETE_ANY:
                        $pickedUpTracks = Mage::getModel('sales/order_shipment_track')->getCollection()
                            ->setShipmentFilter($shipment->getId())
                            ->addAttributeToFilter('udropship_status', array('in'=>array(Unirgy_Dropship_Model_Source::TRACK_STATUS_SHIPPED, Unirgy_Dropship_Model_Source::TRACK_STATUS_DELIVERED)))
                        ;
                        $complete = $pickedUpTracks->count()>0;
                        break;
                    default:
                        $pendingTracks = Mage::getModel('sales/order_shipment_track')->getCollection()
                            ->setShipmentFilter($shipment->getId())
                            ->addAttributeToFilter('udropship_status', array('nin'=>array(Unirgy_Dropship_Model_Source::TRACK_STATUS_SHIPPED, Unirgy_Dropship_Model_Source::TRACK_STATUS_DELIVERED)))
                        ;
                        $complete = !$pendingTracks->count();
                        break;
                }
            } else {
                $complete = false;
            }
        }

        if ($complete) {
            $this->completeShipment($shipment, $save, $delivered);
            $saveShipment = true;
        } elseif ($shipment->getUdropshipStatus()!=Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_PARTIAL) {

            $shipment->setUdropshipStatus(Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_PARTIAL);
            $saveShipment = true;
        }
        $trackingNumber = $track->getNumber();
        $orderId = $order->getIncrementId();

        ///////////TRACKING API
        $tracks = Mage::getModel('track/track')
            ->getCollection()
            ->addFieldToFilter('tracking_number', array('eq' => $trackingNumber))
            ->addFieldToFilter('order_id', array('eq' => $orderId))
            ->getItems();
        $trackId = reset($tracks)->getTrackingId();
        $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');
        $trackings = new AfterShip\Trackings($api_key);
        $responseJson = $trackings->get_by_id($trackId);
        $aftershipStatus = $responseJson['data']['tracking']['tag'];
        if ($aftershipStatus != 'Pending' && $aftershipStatus != 'Info Received' && $aftershipStatus != 'Expired' && $aftershipStatus != '') {
            if ($saveShipment) {
                foreach ($shipment->getAllTracks() as $t) {
                    foreach ($tracks as $_t) {
                        if ($t->getEntityId()==$_t->getEntityId()) {
                            $t->setData($_t->getData());
                            break;
                        }
                    }
                }
                $this->_processTrackStatusSave($save, $shipment);
            }

            if ($complete) {
                $this->completeUdpoIfShipped($shipment, $save);
                $this->completeOrderIfShipped($shipment, $save);
            }
        }

        return $this;
    }
}