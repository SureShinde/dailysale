<?php

class Unirgy_Rma_Urmaadmin_RmaController extends Mage_Adminhtml_Controller_Action
{

    protected function _construct()
    {
        $this->setUsedModuleName('Unirgy_Rma');
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/order')
            ->_addBreadcrumb(Mage::helper('udropship')->__('Sales'), Mage::helper('udropship')->__('Sales'))
            ->_addBreadcrumb(Mage::helper('udropship')->__('uReturn'),Mage::helper('udropship')->__('uReturn'));
        return $this;
    }

    public function indexAction()
    {
        $this->_title(Mage::helper('udropship')->__('Sales'))->_title(Mage::helper('udropship')->__('uReturn'));

        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('urma/adminhtml_rma'))
            ->renderLayout();
    }

    public function viewAction()
    {
        if ($rmaId = $this->getRequest()->getParam('rma_id')) {
            $this->_forward('view', 'urmaadmin_order_rma', null, array('come_from'=>'urma'));
        } else {
            $this->_forward('noRoute');
        }
    }

    public function pdfRmasAction(){
        $rmaIds = $this->getRequest()->getPost('rma_ids');
        if (!empty($rmaIds)) {
            $rmas = Mage::getResourceModel('urma/rma_collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in' => $rmaIds))
                ->load();
            $pdf = $this->_prepareRmaPdf($rmas);

            return $this->_prepareDownloadResponse('rma_'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(), 'application/pdf');
        }
        $this->_redirect('*/*/');
    }

    protected function _prepareRmaPdf($udpos)
    {
        foreach ($udpos as $udpo) {
            $order = $udpo->getOrder();
            $order->setData('__orig_shipping_amount', $order->getShippingAmount());
            $order->setData('__orig_base_shipping_amount', $order->getBaseShippingAmount());
            $order->setShippingAmount($udpo->getShippingAmount());
            $order->setBaseShippingAmount($udpo->getBaseShippingAmount());
        }
        $pdf = Mage::helper('urma')->getVendorPoMultiPdf($udpos);
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
        return Mage::getSingleton('admin/session')->isAllowed('sales/udropship/urma');
    }

    public function exportCsvAction()
    {
        $fileName   = 'rma.csv';
        $grid       = $this->getLayout()->createBlock('urma/adminhtml_rma_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     *  Export order grid to Excel XML format
     */
    public function exportExcelAction()
    {
        $fileName   = 'rma.xml';
        $grid       = $this->getLayout()->createBlock('urma/adminhtml_rma_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
}