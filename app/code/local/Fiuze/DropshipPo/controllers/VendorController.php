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

require_once Mage::getBaseDir('lib') . DS . 'SweetTooth/pest/vendor/autoload.php';
//require_once "app/code/community/Unirgy/Dropship/controllers/VendorController.php";
require_once(Mage::getModuleDir('controllers','Unirgy_DropshipPo').DS.'VendorController.php');

class Fiuze_DropshipPo_VendorController extends Unirgy_DropshipPo_VendorController
{
    public function udpoPostAction()
    {
        $hlp = Mage::helper('udropship');
        $udpoHlp = Mage::helper('udpo');
        $r = $this->getRequest();
        $id = $r->getParam('id');
        $udpo = Mage::getModel('udpo/po')->load($id);
        $vendor = $hlp->getVendor($udpo->getUdropshipVendor());
        $session = $this->_getSession();

        if (!$udpo->getId()) {
            return;
        }
        try {
            $store = $udpo->getOrder()->getStore();

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
            }else{
                $carrierCheck = $this->asTrackNumber($number);
                if(!$carrierCheck){
                    $this->_getSession()->addError($this->__('Cannot save track number. Track number is invalid.'));
                    $this->_getSession()->setData('tracking_id', $number);
                    $this->_forward('udpoInfo');
                    return;
                }
                $uniqueCheck = $this->asIssetNumber($number);
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
            if(!$carrierCheck){
                $this->_getSession()->addError($this->__('Cannot save track number. Track number is invalid.'));
                $this->_getSession()->setData('tracking_id',$number);
                $this->_forward('udpoInfo');
                return;
            }elseif(!$uniqueCheck){
                $this->_getSession()->addError($this->__('Cannot save track number. Track number already exists'));
                $this->_getSession()->setData('tracking_id',$number);
                $this->_forward('udpoInfo');
                return;
            }else{
                $r->setParam('carrier',$key);
                $r->setParam('carrier_title',$carrierTitle);
            }

            $notifyOn = Mage::getStoreConfig('udropship/customer/notify_on', $store);
            $pollTracking = Mage::getStoreConfig('udropship/customer/poll_tracking', $store);
            $poAutoComplete = Mage::getStoreConfig('udropship/vendor/auto_complete_po', $store);
            $autoComplete = Mage::getStoreConfig('udropship/vendor/auto_shipment_complete', $store);

            $poStatusShipped = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_SHIPPED;
            $poStatusDelivered = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_DELIVERED;
            $poStatusCanceled = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_CANCELED;
            $poStatuses = Mage::getSingleton('udpo/source')->setPath('po_statuses')->toOptionHash();
            // if label was printed
            if ($printLabel) {
                $poStatus = $r->getParam('is_shipped') ? $poStatusShipped : Unirgy_DropshipPo_Model_Source::UDPO_STATUS_PARTIAL;
                $isShipped = $r->getParam('is_shipped') ? true : false;
            } else { // if status was set manually
                $poStatus = $r->getParam('status');
                $isShipped = $poStatus == $poStatusShipped || $poStatus==$poStatusDelivered || $autoComplete && ($poStatus==='' || is_null($poStatus));
            }

            //if ($printLabel || $number || ($partial=='ship' && $partialQty)) {
            $partialQty = $partialQty ? $partialQty : array();
            if ($r->getParam('use_label_shipping_amount')) {
                $udpo->setUseLabelShippingAmount(true);
            } elseif ($r->getParam('shipping_amount')) {
                $udpo->setShipmentShippingAmount($r->getParam('shipping_amount'));
            }
            $udpo->setUdpoNoSplitPoFlag(true);
            $shipment = $udpoHlp->createShipmentFromPo($udpo, $partialQty, true, true, true);
            if ($shipment) {
                $shipment->setNewShipmentFlag(true);
                $shipment->setDeleteOnFailedLabelRequestFlag(true);
                $shipment->setCreatedByVendorFlag(true);
            }
            //}

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
                try {
	                $batch = Mage::getModel('udropship/label_batch')
	                    ->setVendor(Mage::getSingleton('udropship/session')->getVendor())
	                    ->processShipments(array($shipment), $data, array('mark_shipped'=>$isShipped));
                } catch (Exception $e) {
                    if ($r->getParam('use_method_code')) {
                        $shipment->setUdropshipMethod($oldUdropshipMethod);
                        $shipment->setUdropshipMethodDescription($oldUdropshipMethodDesc);
                        $shipment->getResource()->saveAttribute($shipment, 'udropship_method');
                        $shipment->getResource()->saveAttribute($shipment, 'udropship_method_description');
                    }
            		throw $e;
                }

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
                } else {
                    if ($batch->getErrors()) {
                    	$batchError = '';
                        foreach ($batch->getErrors() as $error=>$cnt) {
                        	$batchError .= $hlp->__($error, $cnt)." \n";
                        }
                        if ($r->getParam('use_method_code')) {
                            $shipment->setUdropshipMethod($oldUdropshipMethod);
                            $shipment->setUdropshipMethodDescription($oldUdropshipMethodDesc);
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method');
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method_description');
                        }
	            		Mage::throwException($batchError);
                    } else {
                        if ($r->getParam('use_method_code')) {
                            $shipment->setUdropshipMethod($oldUdropshipMethod);
                            $shipment->setUdropshipMethodDescription($oldUdropshipMethodDesc);
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method');
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method_description');
                        }
	                    $batchError = 'No items are available for shipment';
	            		Mage::throwException($batchError);
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

            $udpoStatuses = false;
            if (Mage::getStoreConfig('udropship/vendor/is_restrict_udpo_status')) {
                $udpoStatuses = Mage::getStoreConfig('udropship/vendor/restrict_udpo_status');
                if (!is_array($udpoStatuses)) {
                    $udpoStatuses = explode(',', $udpoStatuses);
                }
            }

            if (!$printLabel && !is_null($poStatus) && $poStatus!=='' && $poStatus!=$udpo->getUdropshipStatus()
                && (!$udpoStatuses || (in_array($udpo->getUdropshipStatus(), $udpoStatuses) && in_array($poStatus, $udpoStatuses)))
            ) {
                $oldStatus = $udpo->getUdropshipStatus();
                $poStatusChanged = false;
                if ($r->getParam('force_status_change_flag')) {
                    $udpo->setForceStatusChangeFlag(true);
                }
                if ($oldStatus==$poStatusCanceled && !$udpo->getForceStatusChangeFlag()) {
                    Mage::throwException(Mage::helper('udpo')->__('Canceled purchase order cannot be reverted'));
                }
                if ($poStatus==$poStatusShipped || $poStatus==$poStatusDelivered) {
                    foreach ($udpo->getShipmentsCollection() as $_s) {
                        if ($_s->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED) {
                            continue;
                        }
                        $hlp->completeShipment($_s, true, $poStatus==$poStatusDelivered);
                    }
                    if (isset($_s)) {
                        $hlp->completeOrderIfShipped($_s, true);
                    }
                    $poStatusChanged = $udpoHlp->processPoStatusSave($udpo, $poStatus, true, $vendor);
                } elseif ($poStatus == $poStatusCanceled) {
                    $udpo->setFullCancelFlag($r->getParam('full_cancel'));
                    $udpo->setNonshippedCancelFlag($r->getParam('nonshipped_cancel'));
                    Mage::helper('udpo')->cancelPo($udpo, true, $vendor);
                    $poStatusChanged = $udpoHlp->processPoStatusSave($udpo, $poStatus, true, $vendor);
                } else {
                    $poStatusChanged = $udpoHlp->processPoStatusSave($udpo, $poStatus, true, $vendor);
                }
                $udpo->getCommentsCollection()->save();
                if ($poStatusChanged) {
                    $session->addSuccess($this->__('Purchase order status has been changed'));
                } else {
                    $session->addError($this->__('Cannot change purchase order status'));
                }
            }

        	if (!empty($shipment) && $shipment->getNewShipmentFlag() && !$shipment->isDeleted()) {
        		$shipment->setNoInvoiceFlag(false);
            	$udpoHlp->invoiceShipment($shipment);
            }

            $comment = $r->getParam('comment');
            if ($comment || $partial=='inform' && $partialQty) {
                if ($partialQty) {
                    $comment .= "\n\nPartial Availability:\n";
                    foreach ($udpo->getAllItems() as $item) {
                        if (!array_key_exists($item->getId(), $partialQty) || '' === $partialQty[$item->getId()]) {
                            continue;
                        }
                        $comment .= $this->__('%s x [%s] %s', $partialQty[$item->getId()], $item->getName(), $item->getSku())."\n";
                    }
                }

                //$udpo->addComment($comment, false, true)->getCommentsCollection()->save();
                Mage::helper('udpo')->sendVendorComment($udpo, $comment);
                $session->addSuccess($this->__('Your comment has been sent to store administrator'));

                $highlight['comment'] = true;
            }

            $session->setHighlight($highlight);
        } catch (Exception $e) {
            $configValue = Mage::getStoreConfig('aftership_options/messages/aftership_validation');
            //save, 422: repeated
            if($configValue) {
                $r = $this->getRequest();
                $id = $r->getParam('id');
                $po = Mage::getModel('udpo/po')->load($id);
                Mage::helper('udropship')->assignVendorSkus($po);
                Mage::helper('udropship/item')->hideVendorIdOption($po);

                $store = Mage::app()->getStore();
                Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
                foreach ($po->getOrder()->getItemsCollection()->getItems() as $item) {
                    $item->setQtyShipped(0);
                    $item->setQtyCanceled(0);
                    $item->save();
                }
                foreach ($po->getItemsCollection()->getItems() as $item) {
                    $item->setQtyShipped(0);
                    $item->setQtyCanceled(0);
                    $item->save();
                }
               // $shipment->delete();
                $po->save();
                Mage::app()->setCurrentStore($store);
            }
            $session->addError($e->getMessage());
        }

        $this->_forward('udpoInfo');
    }

    private function asIssetNumber($num){
        $_curr_carrier = Mage::registry('_curr_carier');
        Mage::unregister('_curr_carier');
        $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');
        $trackings = new AfterShip\Trackings($api_key);
        $response = $trackings->get($_curr_carrier, $num, array('title','order_id'));
        if($response['meta']['code']==4004){
            return true;
        }else{
            return false;
        }
    }
}
