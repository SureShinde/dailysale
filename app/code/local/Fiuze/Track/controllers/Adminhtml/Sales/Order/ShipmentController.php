<?php
/**
 * @author Fiuze Team
 * @category Fiuze
 * @package Fiuze_Track
 * @copyright Copyright (c) 2016 Fiuze
 */

require_once 'Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php';
require_once Mage::getBaseDir('lib') . DS . 'SweetTooth/pest/vendor/autoload.php';

class Aftership_Track_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController{

    /**
     * Check new tracking number action
     */
    public function checkTrackAction(){
        try{
            $carrier = $this->getRequest()->getPost('tracking');
            $obj = reset($carrier);
            $carrierCheck = $this->asTrackNumber($obj['number']);
            $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers();

            if (!$carrierCheck) {
                $response = array(
                    'error' => true,
                    'message' => $this->__('Tracking number is not valid.'),
                );
            } else {
                $carriers = array();
                foreach ($carrierInstances as $code => $carrier) {
                    if ($carrier->isTrackingAvailable()) {
                        $carriers[$code] = $carrier->getConfigData('title');
                    }
                }
                $key = array_search($carrierCheck, $carriers);
                $obj['title']= $carriers[$key];
                $obj['carrier_code']= $key;
            }

        } catch (Mage_Core_Exception $e) {
            $response = array(
                'error' => true,
                'message' => $e->getMessage(),
            );
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => $this->__('Cannot add tracking number.'),
            );
        }
        $response['obj'] = $obj;
        $response = Mage::helper('core')->jsonEncode($response);
        $this->getResponse()->setBody($response);
    }

    /**
     * Add new tracking number action
     */
    public function addTrackAction()
    {
        try {
            $carrier = $this->getRequest()->getPost('carrier');
            $number  = $this->getRequest()->getPost('number');
            $title  = $this->getRequest()->getPost('title');
            if (empty($carrier)) {
                Mage::throwException($this->__('The carrier needs to be specified.'));
            }
            if (empty($number)) {
                Mage::throwException($this->__('Tracking number cannot be empty.'));
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
                $title = $carriers[$key];
                $carrier = $key;//FedEx
            }
            if (!$carrierCheck) {
                Mage::throwException($this->__('Tracking number is not valid.'));
            } else {

            }

            $shipment = $this->_initShipment();
            if ($shipment) {
                $track = Mage::getModel('sales/order_shipment_track')
                    ->setNumber($number)
                    ->setCarrierCode($carrier)
                    ->setTitle($title);
                $shipment->addTrack($track)
                    ->save();

                $this->loadLayout();
                $response = $this->getLayout()->getBlock('shipment_tracking')->toHtml();
            } else {
                $response = array(
                    'error'     => true,
                    'message'   => $this->__('Cannot initialize shipment for adding tracking number.'),
                );
            }
        } catch (Mage_Core_Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage(),
            );
        } catch (Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $this->__('Cannot add tracking number.'),
            );
        }
        if (is_array($response)) {
            $response = Mage::helper('core')->jsonEncode($response);
        }
        $this->getResponse()->setBody($response);
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

    /**
     * Save shipment
     * We can save only new shipment. Existing shipments are not editable
     *
     * @return null
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost('shipment');
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        $tracks = $this->getRequest()->getPost('tracking');
        $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers();
        $carriers = array();
        foreach ($carrierInstances as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $carriers[$code] = $carrier->getConfigData('title');
            }
        }
        foreach($tracks as $key => $number){
            if(!$number['title']){
                $carrierCheck = $this->asTrackNumber($number['number']);
                if(!$carrierCheck){
                    $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
                    $this->_getSession()->addError($this->__('Cannot save shipment. Invalid it is track number'));
                    return;
                }else{
                    $keyCarrier = array_search($carrierCheck, $carriers);
                    $item = array('carrier_code' => $keyCarrier, 'title' => $carriers[$keyCarrier], 'number' =>$number['number']);
                    $tracks[$key] = $item;
                }
            }
        }
        $this->getRequest()->setPost('tracking',$tracks);

        try {
            $shipment = $this->_initShipment();
            if (!$shipment) {
                $this->_forward('noRoute');
                return;
            }

            $shipment->register();
            $comment = '';
            if (!empty($data['comment_text'])) {
                $shipment->addComment(
                    $data['comment_text'],
                    isset($data['comment_customer_notify']),
                    isset($data['is_visible_on_front'])
                );
                if (isset($data['comment_customer_notify'])) {
                    $comment = $data['comment_text'];
                }
            }

            if (!empty($data['send_email'])) {
                $shipment->setEmailSent(true);
            }

            $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
            $responseAjax = new Varien_Object();
            $isNeedCreateLabel = isset($data['create_shipping_label']) && $data['create_shipping_label'];

            if ($isNeedCreateLabel && $this->_createShippingLabel($shipment)) {
                $responseAjax->setOk(true);
            }

            $this->_saveShipment($shipment);

            $shipment->sendEmail(!empty($data['send_email']), $comment);

            $shipmentCreatedMessage = $this->__('The shipment has been created.');
            $labelCreatedMessage    = $this->__('The shipping label has been created.');

            $this->_getSession()->addSuccess($isNeedCreateLabel ? $shipmentCreatedMessage . ' ' . $labelCreatedMessage
                : $shipmentCreatedMessage);
            Mage::getSingleton('adminhtml/session')->getCommentText(true);
        } catch (Mage_Core_Exception $e) {
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage($e->getMessage());
            } else {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage(
                    Mage::helper('sales')->__('An error occurred while creating shipping label.'));
            } else {
                $this->_getSession()->addError($this->__('Cannot save shipment.'));
                $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
            }

        }
        if ($isNeedCreateLabel) {
            $this->getResponse()->setBody($responseAjax->toJson());
        } else {
            $this->_redirect('*/sales_order/view', array('order_id' => $shipment->getOrderId()));
        }
    }

}