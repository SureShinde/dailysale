<?php

class Unirgy_Rma_TrackingController extends Mage_Core_Controller_Front_Action
{
    public function ajaxAction()
    {
        if ($rma = $this->_initRma()) {
            $response = '';
            $tracks = $rma->getTracksCollection();

            $className = Mage::getConfig()->getBlockClassName('core/template');
            $block = new $className();
            $block->setType('core/template')
                ->setIsAnonymous(true)
                ->setTemplate('sales/order/trackinginfo.phtml');

            foreach ($tracks as $track){
                $trackingInfo = $track->getNumberDetail();
                $block->setTrackingInfo($trackingInfo);
                $response .= $block->toHtml()."\n<br />";
            }

            $this->getResponse()->setBody($response);
        }
    }

    /**
     * Popup action
     * Shows tracking info if it's present, otherwise redirects to 404
     */
    public function popupAction()
    {
        $shippingInfoModel = Mage::getModel('urma/shippingInfo')->loadByHash($this->getRequest()->getParam('hash'));
        Mage::register('current_shipping_info', $shippingInfoModel);
        if (count($shippingInfoModel->getTrackingInfo()) == 0) {
            $this->norouteAction();
            return;
        }
        $this->loadLayout();
        $this->renderLayout();
    }


    /**
     * Initialize order model instance
     *
     * @return Mage_Sales_Model_Order || false
     */
    protected function _initRma()
    {
        $id = $this->getRequest()->getParam('rma_id');

        $rma = Mage::getModel('urma/rma')->load($id);

        return $rma;
    }
}
