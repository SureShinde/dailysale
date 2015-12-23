<?php
/**
 * @author Fiuze Team
 * @category Fiuze
 * @package Fiuze_Dropship
 * @copyright Copyright (c) 2016 Fiuze
 */
require_once(Mage::getModuleDir('controllers','Unirgy_Dropship').DS.'VendorController.php');

class Fiuze_Dropship_VendorController extends Unirgy_Dropship_VendorController
{
    public function shipmentPostAction()
    {
        $hlp = Mage::helper('udropship');
        $r = $this->getRequest();
        $id = $r->getParam('id');
        $shipment = Mage::getModel('sales/order_shipment')->load($id);
        $vendor = $hlp->getVendor($shipment->getUdropshipVendor());
        $session = $this->_getSession();

        if (!$shipment->getId()) {
            return;
        }

        try {
            $store = $shipment->getOrder()->getStore();

            $track = null;
            $highlight = array();

            $partial = $r->getParam('partial_availability');
            $partialQty = $r->getParam('partial_qty');

            $printLabel = $r->getParam('print_label');
            $number = $r->getParam('tracking_id');

            $carrier = $r->getParam('carrier');
            $carrierTitle = $r->getParam('carrier_title');

            if (empty($number)) {
                $this->_forward('udpoInfo');
            } else {
                $carrierCheck = $this->asTrackNumber($number);
                $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers();
                $carriers = array();
                foreach ($carrierInstances as $code => $carrier) {
                    if ($carrier->isTrackingAvailable()) {
                        $carriers[$code] = $carrier->getConfigData('title');
                    }
                }
                $key = array_search($carrierCheck, $carriers);
                $carrierTitle = $carriers[$key];
                $carrier = $key;
            }
            if (!$carrierCheck) {
                $this->_getSession()->addError($this->__('Cannot save shipment. Invalid it is track number'));
                $this->_getSession()->setData('tracking_id',$number);
                $this->_forward('shipmentInfo');

                return;
            } else {
                $r->setParam('carrier',$key);
                $r->setParam('carrier_title',$carrierTitle);
            }

            $notifyOn = Mage::getStoreConfig('udropship/customer/notify_on', $store);
            $pollTracking = Mage::getStoreConfig('udropship/customer/poll_tracking', $store);
            $autoComplete = Mage::getStoreConfig('udropship/vendor/auto_shipment_complete', $store);

            $statusShipped = Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_SHIPPED;
            $statusDelivered = Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED;
            $statusCanceled = Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED;
            $statuses = Mage::getSingleton('udropship/source')->setPath('shipment_statuses')->toOptionHash();
            // if label was printed
            if ($printLabel) {
                $status = $r->getParam('is_shipped') ? $statusShipped : Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_PARTIAL;
                $isShipped = $r->getParam('is_shipped') ? true : false;
            } else { // if status was set manually
                $status = $r->getParam('status');
                $isShipped = $status == $statusShipped || $status==$statusDelivered || $autoComplete && ($status === '' || is_null($status));
            }

            // if label to be printed
            if ($printLabel) {
                $data = array(
                    'weight'    => $r->getParam('weight'),
                    'value'     => $r->getParam('value'),
                    'length'    => $r->getParam('length'),
                    'width'     => $r->getParam('width'),
                    'height'    => $r->getParam('height'),
                    'reference' => $r->getParam('reference'),
                    'package_count' => $r->getParam('package_count'),
                );

                $extraLblInfo = $r->getParam('extra_label_info');
                $extraLblInfo = is_array($extraLblInfo) ? $extraLblInfo : array();
                $data = array_merge($data, $extraLblInfo);

                $oldUdropshipMethod = $shipment->getUdropshipMethod();
                $oldUdropshipMethodDesc = $shipment->getUdropshipMethodDescription();
                if ($r->getParam('use_method_code')) {
                    list($useCarrier, $useMethod) = explode('_', $r->getParam('use_method_code'), 2);
                    if (!empty($useCarrier) && !empty($useMethod)) {
                        $shipment->setUdropshipMethod($r->getParam('use_method_code'));
                        $carrierMethods = Mage::helper('udropship')->getCarrierMethods($useCarrier);
                        $shipment->setUdropshipMethodDescription(
                            Mage::getStoreConfig('carriers/'.$useCarrier.'/title', $shipment->getOrder()->getStoreId())
                            .' - '.$carrierMethods[$useMethod]
                        );
                    }
                }

                // generate label
                $batch = Mage::getModel('udropship/label_batch')
                    ->setVendor(Mage::getSingleton('udropship/session')->getVendor())
                    ->processShipments(array($shipment), $data, array('mark_shipped'=>$isShipped));

                // if batch of 1 label is successfull
                if ($batch->getShipmentCnt()) {
                    $url = Mage::getUrl('udropship/vendor/reprintLabelBatch', array('batch_id'=>$batch->getId()));
                    Mage::register('udropship_download_url', $url);

                    if (($track = $batch->getLastTrack())) {
                        $session->addSuccess('Label was succesfully created');
                        Mage::helper('udropship')->addShipmentComment(
                            $shipment,
                            $this->__('%s printed label ID %s', $vendor->getVendorName(), $track->getNumber())
                        );
                        $shipment->save();
                        $highlight['tracking'] = true;
                    }
                    if ($r->getParam('use_method_code')) {
                        $shipment->setUdropshipMethod($oldUdropshipMethod);
                        $shipment->setUdropshipMethodDescription($oldUdropshipMethodDesc);
                        $shipment->getResource()->saveAttribute($shipment, 'udropship_method');
                        $shipment->getResource()->saveAttribute($shipment, 'udropship_method_description');
                    }
                } else {
                    if ($batch->getErrors()) {
                        foreach ($batch->getErrors() as $error=>$cnt) {
                            $session->addError($hlp->__($error, $cnt));
                        }
                        if ($r->getParam('use_method_code')) {
                            $shipment->setUdropshipMethod($oldUdropshipMethod);
                            $shipment->setUdropshipMethodDescription($oldUdropshipMethodDesc);
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method');
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method_description');
                        }
                    } else {
                        $session->addError($this->__('No items are available for shipment'));
                        if ($r->getParam('use_method_code')) {
                            $shipment->setUdropshipMethod($oldUdropshipMethod);
                            $shipment->setUdropshipMethodDescription($oldUdropshipMethodDesc);
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method');
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method_description');
                        }
                    }
                }

            } elseif ($number) { // if tracking id was added manually
                $method = explode('_', $shipment->getUdropshipMethod(), 2);
                $title = Mage::getStoreConfig('carriers/'.$method[0].'/title', $store);
                $_carrier = $method[0];
                if (!empty($carrier) && !empty($carrierTitle)) {
                    $_carrier = $carrier;
                    $title = $carrierTitle;
                }
                $track = Mage::getModel('sales/order_shipment_track')
                    ->setNumber($number)
                    ->setCarrierCode($_carrier)
                    ->setTitle($title);

                $shipment->addTrack($track);

                Mage::helper('udropship')->processTrackStatus($track, true, $isShipped);

                Mage::helper('udropship')->addShipmentComment(
                    $shipment,
                    $this->__('%s added tracking ID %s', $vendor->getVendorName(), $number)
                );
                $shipment->save();
                $session->addSuccess($this->__('Tracking ID has been uploaded and we will email the results of the upload within 24 hours.'));

                $highlight['tracking'] = true;
            }

            // if track was generated - for both label and manual tracking id
            /*
            if ($track) {
                // if poll tracking is enabled for the vendor
                if ($pollTracking && $vendor->getTrackApi()) {
                    $track->setUdropshipStatus(Unirgy_Dropship_Model_Source::TRACK_STATUS_PENDING);
                    $isShipped = false;
                } else { // otherwise process track
                    $track->setUdropshipStatus(Unirgy_Dropship_Model_Source::TRACK_STATUS_READY);
                    Mage::helper('udropship')->processTrackStatus($track, true, $isShipped);
                }
            */
            // if tracking id added manually and new status is not current status
            $shipmentStatuses = false;
            if (Mage::getStoreConfig('udropship/vendor/is_restrict_shipment_status')) {
                $shipmentStatuses = Mage::getStoreConfig('udropship/vendor/restrict_shipment_status');
                if (!is_array($shipmentStatuses)) {
                    $shipmentStatuses = explode(',', $shipmentStatuses);
                }
            }
            if (!$printLabel && !is_null($status) && $status!=='' && $status!=$shipment->getUdropshipStatus()
                && (!$shipmentStatuses || (in_array($shipment->getUdropshipStatus(), $shipmentStatuses) && in_array($status, $shipmentStatuses)))
            ) {
                $oldStatus = $shipment->getUdropshipStatus();
                if (($oldStatus==$statusShipped || $oldStatus==$statusDelivered)
                    && $status!=$statusShipped && $status!=$statusDelivered && $hlp->isUdpoActive()
                ) {
                    Mage::helper('udpo')->revertCompleteShipment($shipment, true);
                } elseif ($oldStatus==$statusCanceled && $hlp->isUdpoActive()) {
                    Mage::throwException(Mage::helper('udpo')->__('Canceled shipment cannot be reverted'));
                }
                $changedComment = $this->__('%s has changed the shipment status to %s', $vendor->getVendorName(), $statuses[$status]);
                $triedToChangeComment = $this->__('%s tried to change the shipment status to %s', $vendor->getVendorName(), $statuses[$status]);
                if ($status==$statusShipped || $status==$statusDelivered) {
                    $hlp->completeShipment($shipment, true, $status==$statusDelivered);
                    $hlp->completeOrderIfShipped($shipment, true);
                    $hlp->completeUdpoIfShipped($shipment, true);
                    Mage::helper('udropship')->addShipmentComment(
                        $shipment,
                        $changedComment
                    );
                } elseif ($status == $statusCanceled && $hlp->isUdpoActive()) {
                    if (Mage::helper('udpo')->cancelShipment($shipment, true)) {
                        Mage::helper('udropship')->addShipmentComment(
                            $shipment,
                            $changedComment
                        );
                        Mage::helper('udpo')->processPoStatusSave(Mage::helper('udpo')->getShipmentPo($shipment), Unirgy_DropshipPo_Model_Source::UDPO_STATUS_PARTIAL, true, $vendor);
                    } else {
                        Mage::helper('udropship')->addShipmentComment(
                            $shipment,
                            $triedToChangeComment
                        );
                    }
                } else {
                    $shipment->setUdropshipStatus($status)->save();
                    Mage::helper('udropship')->addShipmentComment(
                        $shipment,
                        $changedComment
                    );
                }
                $shipment->getCommentsCollection()->save();
                $session->addSuccess($this->__('Shipment status has been changed'));
            }

            $comment = $r->getParam('comment');
            if ($comment || $partial=='inform' && $partialQty) {
                if ($partialQty) {
                    $comment .= "\n\nPartial Availability:\n";
                    foreach ($shipment->getAllItems() as $item) {
                        if (!array_key_exists($item->getId(), $partialQty) || '' === $partialQty[$item->getId()]) {
                            continue;
                        }
                        $comment .= $this->__('%s x [%s] %s', $partialQty[$item->getId()], $item->getName(), $item->getSku())."\n";
                    }
                }

                Mage::helper('udropship')->sendVendorComment($shipment, $comment);
                $session->addSuccess($this->__('Your comment has been sent to store administrator'));

                $highlight['comment'] = true;
            }

            $deleteTrack = $r->getParam('delete_track');
            if ($deleteTrack) {
                $track = Mage::getModel('sales/order_shipment_track')->load($deleteTrack);
                if ($track->getId()) {

                    try {
                        $labelModel = Mage::helper('udropship')->getLabelCarrierInstance($track->getCarrierCode())->setVendor($vendor);
                        try {
                            $labelModel->voidLabel($track);
                            Mage::helper('udropship')->addShipmentComment(
                                $shipment,
                                $this->__('%s voided tracking ID %s', $vendor->getVendorName(), $track->getNumber())
                            );
                            $session->addSuccess($this->__('Track %s was voided', $track->getNumber()));
                        } catch (Exception $e) {
                            Mage::helper('udropship')->addShipmentComment(
                                $shipment,
                                $this->__('%s attempted to void tracking ID %s: %s', $vendor->getVendorName(), $track->getNumber(), $e->getMessage())
                            );
                            $session->addSuccess($this->__('Problem voiding track %s: %s', $track->getNumber(), $e->getMessage()));
                        }
                    } catch (Exception $e) {
                        // doesn't support voiding
                    }

                    $track->delete();
                    if ($track->getPackageCount()>1) {
                        foreach (Mage::getResourceModel('sales/order_shipment_track_collection')
                                     ->addAttributeToFilter('master_tracking_id', $track->getMasterTrackingId())
                                 as $_track
                        ) {
                            $_track->delete();
                        }
                    }
                    Mage::helper('udropship')->addShipmentComment(
                        $shipment,
                        $this->__('%s deleted tracking ID %s', $vendor->getVendorName(), $track->getNumber())
                    );
                    $shipment->getCommentsCollection()->save();
                    #$save = true;
                    $highlight['tracking'] = true;
                    $session->addSuccess($this->__('Track %s was deleted', $track->getNumber()));
                } else {
                    $session->addError($this->__('Track %s was not found', $track->getNumber()));
                }
            }

            $session->setHighlight($highlight);
        } catch (Exception $e) {
            $session->addError($e->getMessage());
        }
        $this->_forward('shipmentInfo');
    }

    public function asTrackNumber($trackingNumber){
        $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');
        $courier = new AfterShip\Couriers($api_key);
        $response = $courier->detect($trackingNumber);
        $data = $response['data'];
        $courier = reset($data['couriers']);
        switch($courier['name']){
            case 'DHL eCommerce':
                $nameCourier = reset(explode(' ', $courier['name']));
                $data['total'] ? $result = $nameCourier : $result = false;
                return $result;
            default:
                $data['total'] ? $result = $courier['other_name'] : $result = false;
                return $result;
                break;

        }
    }
}
