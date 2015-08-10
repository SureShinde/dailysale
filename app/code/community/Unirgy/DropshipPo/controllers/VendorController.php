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
require_once "app/code/community/Unirgy/Dropship/controllers/VendorController.php";

class Unirgy_DropshipPo_VendorController extends Unirgy_Dropship_VendorController
{
    public function indexAction()
    {
    	$_hlp = Mage::helper('udropship');
        switch ($this->getRequest()->getParam('submit_action')) {
            case 'updateUdpoStatus':
                $this->_forward('updateUdpoStatus', 'vendor', 'udpo');
                return;
            case 'udpoMultiPdf':
                $this->_forward('udpoMultiPdf', 'vendor', 'udpo');
                return;
            case 'udpoLabelBatch':
                $this->_forward('udpoLabelBatch', 'vendor', 'udpo');
                return;
	        case 'labelBatch':
	        case $_hlp->__('Create and Download Labels Batch'):
	            $this->_forward('labelBatch', 'vendor', 'udpo');
	            return;

	        case 'existingLabelBatch':
	            $this->_forward('existingLabelBatch', 'vendor', 'udpo');
	            return;

	        case 'packingSlips':
	        case $_hlp->__('Download Packing Slips'):
	            $this->_forward('packingSlips', 'vendor', 'udpo');
	            return;

	        case 'updateShipmentsStatus':
	            $this->_forward('updateShipmentsStatus', 'vendor', 'udpo');
	            return;
            case 'udbatchExport':
                $this->_forward('exportOrders', 'vendor_batch', 'udbatch');
                return;

            case 'createShipmentAndEmail':
                $this->getRequest()->setParam('send_customer_notification', true);
            case 'createShipment':
                $this->_forward('createShipment', 'vendor', 'udpo');
                return;

            default:
                return parent::indexAction();
        }
    }

    public function udpoInfoAction()
    {
        $this->_setTheme();
        $this->loadLayout(false);

        $block = $this->getLayout()->getBlock('info');
        Mage::helper('udropship')->applyItemRenderers('sales_order_shipment', $block, '/checkout/', false);
        $block->addItemRender('bundle', 'udpo/orderItemsRenderer_bundle', 'unirgy/udpo/order/items/renderer/bundle.phtml');
        if (($url = Mage::registry('udropship_download_url'))) {
            $block->setDownloadUrl($url);
        }
        $this->_initLayoutMessages('udropship/session');

        $this->getResponse()->setBody($block->toHtml());
    }

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
                $this->_getSession()->addError($this->__('Cannot save shipment. Invalid it is track number'));
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
                $session->addSuccess($this->__('Tracking ID has been added'));

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
                $shipment->delete();
                $po->save();
                Mage::app()->setCurrentStore($store);
            }
            $session->addError($e->getMessage());
        }

        $this->_forward('udpoInfo');
    }

	public function addUdpoCommentAction()
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

            $highlight = array();

            $partial = $r->getParam('partial_availability');
            $partialQty = $r->getParam('partial_qty');

            $notifyOn = Mage::getStoreConfig('udropship/customer/notify_on', $store);
            $pollTracking = Mage::getStoreConfig('udropship/customer/poll_tracking', $store);
            $poAutoComplete = Mage::getStoreConfig('udropship/vendor/auto_complete_po', $store);
            $autoComplete = Mage::getStoreConfig('udropship/vendor/auto_shipment_complete', $store);

            $poStatusShipped = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_SHIPPED;
            $poStatusDelivered = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_DELIVERED;
            $poStatusCanceled = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_CANCELED;
            $poStatuses = Mage::getSingleton('udpo/source')->setPath('po_statuses')->toOptionHash();
            // if label was printed
            $poStatus = $r->getParam('status');
            $isShipped = $poStatus == $poStatusShipped || $poStatus==$poStatusDelivered || $autoComplete && ($poStatus==='' || is_null($poStatus));

            $udpoStatuses = false;
            if (Mage::getStoreConfig('udropship/vendor/is_restrict_udpo_status')) {
                $udpoStatuses = Mage::getStoreConfig('udropship/vendor/restrict_udpo_status');
                if (!is_array($udpoStatuses)) {
                    $udpoStatuses = explode(',', $udpoStatuses);
                }
            }

            if (!is_null($poStatus) && $poStatus!=='' && $poStatus!=$udpo->getUdropshipStatus()
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

            $comment = $r->getParam('comment');
            if ($comment || $partial=='inform' && $partialQty) {
                if ($partialQty) {
                    $comment .= "\n\nPartial Availability:\n";
                    foreach ($udpo->getAllItems() as $item) {
                        if (empty($partialQty[$item->getId()])) {
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
            $session->addError($e->getMessage());
        }

        $this->_forward('udpoInfo');
    }

    public function udpoDeleteTrackAction()
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
        $deleteTrack = $r->getParam('delete_track');
        if ($deleteTrack) {
            $track = Mage::getModel('sales/order_shipment_track')->load($deleteTrack);
            if ($track->getId()) {

                try {
                    $labelModel = Mage::helper('udropship')->getLabelCarrierInstance($track->getCarrierCode())->setVendor($vendor);
                    try {
                        $labelModel->voidLabel($track);
                        $udpo->addComment($this->__('%s voided tracking ID %s', $vendor->getVendorName(), $track->getNumber()));
                        $session->addSuccess($this->__('Track %s was voided', $track->getNumber()));
                    } catch (Exception $e) {
                        $udpo->addComment($this->__('%s attempted to void tracking ID %s: %s', $vendor->getVendorName(), $track->getNumber(), $e->getMessage()));
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
                $udpo->addComment($this->__('%s deleted tracking ID %s', $vendor->getVendorName(), $track->getNumber()))->save();
                #$save = true;
                $highlight['tracking'] = true;
                $session->addSuccess($this->__('Track %s was deleted', $track->getNumber()));
            } else {
                $session->addError($this->__('Track %s was not found', $track->getNumber()));
            }
        }
        $this->_forward('udpoInfo');
    }

    public function shipmentDeleteTrackAction()
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
                $shipment->save();
                #$save = true;
                $highlight['tracking'] = true;
                $session->addSuccess($this->__('Track %s was deleted', $track->getNumber()));
            } else {
                $session->addError($this->__('Track %s was not found', $track->getNumber()));
            }
        }
        $this->_forward('shipmentInfo');
    }

    public function updateUdpoStatusAction()
    {
        try {
            $udpos = $this->getVendorPoCollection();
            $r = $this->getRequest();
            $poStatus = $this->getRequest()->getParam('update_status');

            $poStatusShipped = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_SHIPPED;
            $poStatusDelivered = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_DELIVERED;
            $poStatusCanceled = Unirgy_DropshipPo_Model_Source::UDPO_STATUS_CANCELED;
            $poStatuses = Mage::getSingleton('udpo/source')->setPath('po_statuses')->toOptionHash();

            if (!$udpos->getSize()) {
                Mage::throwException($this->__('No purchase orders found for these criteria'));
            }
            if (is_null($poStatus) || $poStatus==='') {
                Mage::throwException($this->__('No status selected'));
            }

            $vendorId = $this->_getSession()->getId();
            $vendor = Mage::helper('udropship')->getVendor($vendorId);

            $hlp = Mage::helper('udropship');
            $udpoHlp = Mage::helper('udpo');

            $udpoStatuses = false;
            if (Mage::getStoreConfig('udropship/vendor/is_restrict_udpo_status')) {
                $udpoStatuses = Mage::getStoreConfig('udropship/vendor/restrict_udpo_status');
                if (!is_array($udpoStatuses)) {
                    $udpoStatuses = explode(',', $udpoStatuses);
                }
            }

            foreach ($udpos as $udpo) {
                if (!is_null($poStatus) && $poStatus!=='' && $poStatus!=$udpo->getUdropshipStatus()
                    && (!$udpoStatuses || (in_array($udpo->getUdropshipStatus(), $udpoStatuses) && in_array($poStatus, $udpoStatuses)))
                ) {
                    $oldStatus = $udpo->getUdropshipStatus();
                    if ($oldStatus==$poStatusCanceled) {
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
                        $udpoHlp->processPoStatusSave($udpo, $poStatus, true, $vendor);
                    } elseif ($poStatus == $poStatusCanceled) {
                        $udpo->setFullCancelFlag($r->getParam('full_cancel'));
                    	$udpo->setNonshippedCancelFlag($r->getParam('nonshipped_cancel'));
                        Mage::helper('udpo')->cancelPo($udpo, true, $vendor);
                        $udpoHlp->processPoStatusSave($udpo, $poStatus, true, $vendor);
                    } else {
                        $udpoHlp->processPoStatusSave($udpo, $poStatus, true, $vendor);
                    }
                }
            }
            $this->_getSession()->addSuccess($this->__('Purchase Order status has been updated for the selected orders'));
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__($e->getMessage()));
        }
        $this->_redirect('udpo/vendor/', array('_current'=>true, '_query'=>array('submit_action'=>'')));
    }

    public function udpoPdfAction()
    {
        try {
            $id = $this->getRequest()->getParam('udpo_id');
            if (!$id) {
                Mage::throwException('Invalid purchase order ID is supplied');
            }

            $udpos = Mage::getResourceModel('udpo/po_collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', $id)
                ->load();
            if (!$udpos->getSize()) {
                Mage::throwException('No purchase order found with supplied IDs');
            }

            return $this->_preparePoMultiPdf($udpos);

        } catch (Exception $e) {
            $this->_getSession()->addError($this->__($e->getMessage()));
        }
        $this->_redirect('udpo/vendor/');
    }

    public function udpoMultiPdfAction()
    {
    	$result = array();
        try {
            $udpos = $this->getVendorPoCollection();
            if (!$udpos->getSize()) {
                Mage::throwException('No purchase orders found for these criteria');
            }

            return $this->_preparePoMultiPdf($udpos);

        } catch (Exception $e) {
        if ($this->getRequest()->getParam('use_json_response')) {
        		$result = array(
        			'error'=>true,
        			'message'=>$e->getMessage()
        		);
        	} else {
            	$this->_getSession()->addError($this->__($e->getMessage()));
        	}
        }
        if ($this->getRequest()->getParam('use_json_response')) {
        	$this->getResponse()->setBody(
        		Mage::helper('core')->jsonEncode($result)
        	);
        } else {
        	$this->_redirect('udpo/vendor/', array('_current'=>true, '_query'=>array('submit_action'=>'')));
        }
    }

    protected function _preparePoMultiPdf($udpos)
    {
        $vendorId = $this->_getSession()->getId();
        $vendor = Mage::helper('udropship')->getVendor($vendorId);

        foreach ($udpos as $udpo) {
            if ($udpo->getUdropshipVendor()!=$vendorId) {
                Mage::throwException('You are not authorized to print this purchase order');
            }
        }

        if (Mage::getStoreConfig('udropship/purchase_order/ready_on_pdf')) {
            $udpoHlp = Mage::helper('udpo');
            foreach ($udpos as $udpo) {
                $udpo->addComment($this->__('%s printed purchase order pdf', $vendor->getVendorName()), false, true);
                if ($udpo->getUdropshipStatus()==Unirgy_DropshipPo_Model_Source::UDPO_STATUS_PENDING) {
                    $udpoHlp->processPoStatusSave($udpo, Unirgy_DropshipPo_Model_Source::UDPO_STATUS_READY, true);
                }
                $udpo->save();
            }
        }

        foreach ($udpos as $udpo) {
            $order = $udpo->getOrder();
            $order->setData('__orig_shipping_amount', $order->getShippingAmount());
            $order->setData('__orig_base_shipping_amount', $order->getBaseShippingAmount());
            $order->setShippingAmount($udpo->getShippingAmount());
            $order->setBaseShippingAmount($udpo->getBaseShippingAmount());
        }

        $theme = explode('/', Mage::getStoreConfig('udropship/admin/interface_theme', 0));
        Mage::getDesign()->setArea('adminhtml')
            ->setPackageName(!empty($theme[0]) ? $theme[0] : 'default')
            ->setTheme(!empty($theme[1]) ? $theme[1] : 'default');

        $pdf = Mage::helper('udpo')->getVendorPoMultiPdf($udpos);
        $filename = 'purchase_order_'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf';

        foreach ($udpos as $udpo) {
            $order = $udpo->getOrder();
            $order->setShippingAmount($order->getData('__orig_shipping_amount'));
            $order->setBaseShippingAmount($order->getData('__orig_base_shipping_amount'));
        }

        Mage::helper('udropship')->sendDownload($filename, $pdf->render(), 'application/x-pdf');
    }

    public function udpoLabelBatchAction()
    {
    	$result = array();
        try {
            $udpoHlp = Mage::helper('udpo');
            $udpos = $this->getVendorPoCollection();
            if (!$udpos->getSize()) {
                Mage::throwException('No purchase orders found for these criteria');
            }
            $shipments = array();
            foreach ($udpos as $udpo) {
                $udpoHlp->createReturnAllShipments=true;
                if (($_shipments = $udpoHlp->createShipmentFromPo($udpo, array(), true, true, true))) {
                    foreach ($_shipments as $_shipment) {
                        $_shipment->setNewShipmentFlag(true);
                        $_shipment->setDeleteOnFailedLabelRequestFlag(true);
                        $_shipment->setCreatedByVendorFlag(true);
                        $shipments[] = $_shipment;
                    }
                } else {
                    foreach ($udpo->getShipmentsCollection() as $_s) {
                        if ($_s->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED) {
                            continue;
                        }
                        $shipments[] = $_s;
                        break;
                    }
                }
                $udpoHlp->createReturnAllShipments=false;
            }
            if (empty($shipments)) {
                Mage::throwException('Cannot create shipments (maybe nothing to create)');
            }

            $labelBatch = Mage::getModel('udropship/label_batch')
                ->setVendor(Mage::getSingleton('udropship/session')->getVendor())
                ->processShipments($shipments, array(), array('mark_shipped'=>true));

            if (!empty($shipments)) {
            	foreach ($shipments as $shipment) {
            		if (!$shipment->isDeleted()) {
            			$shipment->setNoInvoiceFlag(false);
            			$udpoHlp->invoiceShipment($shipment);
            		}
            	}
            }
            $labelBatch->prepareLabelsDownloadResponse();

        } catch (Exception $e) {
            Mage::logException($e);
        	if ($this->getRequest()->getParam('use_json_response')) {
        		$result = array(
        			'error'=>true,
        			'message'=>$e->getMessage()
        		);
        	} else {
            	$this->_getSession()->addError($this->__($e->getMessage()));
        	}
        }
    	if ($this->getRequest()->getParam('use_json_response')) {
        	$this->getResponse()->setBody(
        		Mage::helper('core')->jsonEncode($result)
        	);
        } else {
        	$this->_redirect('udpo/vendor/', array('_current'=>true, '_query'=>array('submit_action'=>'')));
        }
    }

    public function createShipmentAction()
    {
        $result = array();
        try {
            $udpoHlp = Mage::helper('udpo');
            $udpos = $this->getVendorPoCollection();
            if (!$udpos->getSize()) {
                Mage::throwException('No purchase orders found for these criteria');
            }
            $createdShipments = 0;
            $shipments = array();
            foreach ($udpos as $udpo) {
                $udpoHlp->createReturnAllShipments=true;
                if (($_shipments = $udpoHlp->createShipmentFromPo($udpo, array(), true, true, true))) {
                    foreach ($_shipments as $_shipment) {
                        $createdShipments++;
                        $_shipment->setNewShipmentFlag(true);
                        $_shipment->setDeleteOnFailedLabelRequestFlag(true);
                        $_shipment->setCreatedByVendorFlag(true);
                        $shipments[] = $_shipment;
                    }
                } elseif ($this->getRequest()->getParam('send_customer_notification')) {
                    foreach ($udpo->getShipmentsCollection() as $_s) {
                        if ($_s->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED) {
                            continue;
                        }
                        $shipments[] = $_s;
                        break;
                    }
                }
                $udpoHlp->createReturnAllShipments=false;
            }
            if (empty($shipments)) {
                Mage::throwException('Cannot create shipments (maybe nothing to create)');
            }

            $this->_getSession()->addSuccess($this->__('Created %d shipments', $createdShipments));

            $notificationsSent = 0;
            if ($this->getRequest()->getParam('send_customer_notification')) {
                foreach ($shipments as $shipment) {
                    $shipment->sendEmail();
                    $shipment->setEmailSent(true);
                    $notificationsSent++;
                }
                $this->_getSession()->addSuccess($this->__('Sent %d shipment customer notifications', $notificationsSent));
            }

        } catch (Exception $e) {
            Mage::logException($e);
            if ($this->getRequest()->getParam('use_json_response')) {
                $result = array(
                    'error'=>true,
                    'message'=>$e->getMessage()
                );
            } else {
                $this->_getSession()->addError($this->__($e->getMessage()));
            }
        }
        if ($this->getRequest()->getParam('use_json_response')) {
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode($result)
            );
        } else {
            $this->_redirect('udpo/vendor/', array('_current'=>true, '_query'=>array('submit_action'=>'')));
        }
    }

    public function getVendorPoCollection()
    {
        return Mage::helper('udpo')->getVendorPoCollection();
    }

    public function getVendorShipmentCollection()
    {
        return Mage::helper('udpo')->getVendorShipmentCollection();
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
