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
 
class Unirgy_DropshipPo_Udpoadmin_PoController extends Mage_Adminhtml_Controller_Action
{

    protected function _construct()
    {
        $this->setUsedModuleName('Unirgy_DropshipPo');
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/order')
            ->_addBreadcrumb(Mage::helper('udropship')->__('Sales'), Mage::helper('udropship')->__('Sales'))
            ->_addBreadcrumb(Mage::helper('udropship')->__('Dropship'), Mage::helper('udropship')->__('Dropship'))
            ->_addBreadcrumb(Mage::helper('udropship')->__('Purchase Orders'),Mage::helper('udropship')->__('Purchase Orders'));
        return $this;
    }

    public function indexAction()
    {
        $this->_title(Mage::helper('udropship')->__('Sales'))->_title(Mage::helper('udropship')->__('Dropship'))->_title(Mage::helper('udropship')->__('Purchase Orders'));

        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('udpo/adminhtml_po'))
            ->renderLayout();
    }

    public function viewAction()
    {
        if ($shipmentId = $this->getRequest()->getParam('udpo_id')) {
            $this->_forward('view', 'udpoadmin_order_po', null, array('come_from'=>'udpo'));
        } else {
            $this->_forward('noRoute');
        }
    }

    public function pdfUdposAction(){
        $udpoIds = $this->getRequest()->getPost('udpo_ids');
        if (!empty($udpoIds)) {
            $udpos = Mage::getResourceModel('udpo/po_collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in' => $udpoIds))
                ->load();
            $pdf = $this->_prepareUdpoPdf($udpos);

            return $this->_prepareDownloadResponse('purchase_order_'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(), 'application/pdf');
        }
        $this->_redirect('*/*/');
    }

    public function resendUdposAction(){
        $udpoIds = $this->getRequest()->getPost('udpo_ids');
        if (!empty($udpoIds)) {
            try {
                $udpos = Mage::getResourceModel('udpo/po_collection')
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('entity_id', array('in' => $udpoIds))
                    ->load();

                foreach ($udpos as $udpo) {
                    $udpo->afterLoad();
                    $udpo->setResendNotificationFlag(true);
                    $vendor = $udpo->getVendor();
                    $method = $vendor->getNewOrderNotifications();
                    if ($method==-1 && $udpo->getData('is_vendor_notified')) {
                        $udpo->setResendNotificationFlag(1);
                        Mage::helper('udpo')->sendNewPoNotificationEmail($udpo);
                        Mage::helper('udropship')->processQueue();
                    } else {
                        Mage::helper('udpo')->sendVendorNotification($udpo);
                    }
                }
                Mage::helper('udropship')->processQueue();

                $this->_getSession()->addSuccess(Mage::helper('udropship')->__('%s notifications sent.', count($udpoIds)));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError(Mage::helper('udropship')->__('Cannot save shipment.'));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction(){
        $hlp = Mage::helper('udropship');
        $udpoHlp = Mage::helper('udpo');
        $udpoIds = $this->getRequest()->getPost('udpo_ids');
        if (!empty($udpoIds)) {
            try {
                $udpos = Mage::getResourceModel('udpo/po_collection')
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('entity_id', array('in' => $udpoIds))
                    ->load();

                foreach ($udpos as $udpo) {
                    $udpo->afterLoad();
                    $udpo->setFullCancelFlag(1);
                    $udpo->setNonshippedCancelFlag(1);
                    $udpo->setForceStatusChangeFlag(1);
                    $udpo->setResendNotificationFlag(1);
                    Mage::helper('udpo')->cancelPo($udpo, true);
                    $udpoHlp->processPoStatusSave($udpo, Unirgy_DropshipPo_Model_Source::UDPO_STATUS_CANCELED, true, null, null, false, false);
                    Mage::helper('udpo')->sendPoDeleteVendorNotification($udpo);
                }
                Mage::helper('udropship')->processQueue();

                foreach ($udpos as $udpo) {
                    foreach ($udpo->getShipmentsCollection() as $_shipment) {
                        $_shipment->delete();
                    }
                    $udpo->delete();
                }

                $this->_getSession()->addSuccess(Mage::helper('udropship')->__('%s POs deleted.', count($udpoIds)));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError(Mage::helper('udropship')->__('Cannot save delete POs.'));
            }
        }
        $this->_redirect('*/*/');
    }

    protected function _prepareUdpoPdf($udpos)
    {
        foreach ($udpos as $udpo) {
            $order = $udpo->getOrder();
            $order->setData('__orig_shipping_amount', $order->getShippingAmount());
            $order->setData('__orig_base_shipping_amount', $order->getBaseShippingAmount());
            $order->setShippingAmount($udpo->getShippingAmount());
            $order->setBaseShippingAmount($udpo->getBaseShippingAmount());
        }
        $pdf = Mage::helper('udpo')->getVendorPoMultiPdf($udpos);
        foreach ($udpos as $udpo) {
            $order = $udpo->getOrder();
            $order->setShippingAmount($order->getData('__orig_shipping_amount'));
            $order->setBaseShippingAmount($order->getData('__orig_base_shipping_amount'));
        }
        return $pdf;
    }

    public function printAction()
    {
        if ($udoId = $this->getRequest()->getParam('udpo_id')) { 
            if (($udpo = Mage::getModel('udpo/po')->load($udoId)) && $udpo->getId()) {
                if ($udpo->getStoreId()) {
                    Mage::app()->setCurrentStore($udpo->getStoreId());
                }
                $pdf = $this->_prepareUdpoPdf(array($udpo));
                $this->_prepareDownloadResponse('purchase_order_'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(), 'application/pdf');
            }
        }
        else {
            $this->_forward('noRoute');
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/udropship/udpo');
    }

    public function exportCsvAction()
    {
        $fileName   = 'po.csv';
        $grid       = $this->getLayout()->createBlock('udpo/adminhtml_po_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    public function exportExcelAction()
    {
        $fileName   = 'po.xml';
        $grid       = $this->getLayout()->createBlock('udpo/adminhtml_po_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
}