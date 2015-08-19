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
 * @package    Unirgy_Rma
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

require_once "app/code/community/Unirgy/Dropship/controllers/VendorController.php";
 
class Unirgy_Rma_VendorController extends Unirgy_Dropship_VendorController
{
    public function indexAction()
    {
    	$_hlp = Mage::helper('udropship');
        switch ($this->getRequest()->getParam('submit_action')) {
            case 'updateUrmaStatus':
                $this->_forward('updateUrmaStatus', 'vendor', 'urma');
                return;
            default:
                $this->_renderPage(null, 'urmas');
        }
    }
    
    public function urmaInfoAction()
    {
        $this->_setTheme();
        $this->loadLayout(false);

        $block = $this->getLayout()->getBlock('info');
        Mage::helper('udropship')->applyItemRenderers('sales_order_rma', $block, '/checkout/', false);
        $this->_initLayoutMessages('udropship/session');

        $this->getResponse()->setBody($block->toHtml());
    }
    
	public function addUrmaCommentAction()
    {
        $hlp = Mage::helper('udropship');
        $urmaHlp = Mage::helper('urma');
        $r = $this->getRequest();
        $id = $r->getParam('id');
        $urma = Mage::getModel('urma/rma')->load($id);
        $vendor = $hlp->getVendor($urma->getUdropshipVendor());
        $session = $this->_getSession();

        if (!$urma->getId()) {
            return;
        }

        try {
            $track = null;
            $highlight = array();

            $partial = $r->getParam('partial_availability');
            $partialQty = $r->getParam('partial_qty');

            $number = $r->getParam('tracking_id');
            $carrier = $r->getParam('carrier');
            $title  = $r->getParam('carrier_title');

            $rmaStatus = $r->getParam('status');

            if ($number) { // if tracking id was added manually
                $track = Mage::getModel('urma/rma_track')
                    ->setNumber($number)
                    ->setCarrierCode($carrier)
                    ->setTitle($title);

                $urma->addTrack($track);

                $urma->addComment(
                    $this->__('%s added tracking ID %s', $vendor->getVendorName(), $number),
                    false, true
                );
                $urma->setData('___dummy',1)->save();
                $session->addSuccess($this->__('Tracking ID has been uploaded and we will email the results of the upload within 24 hours.'));

                $highlight['tracking'] = true;
            }
            
            if (!is_null($rmaStatus) && $rmaStatus!=='' && $rmaStatus!=$urma->getUdropshipStatus()) {
                $rmaStatusChanged = $urmaHlp->processRmaStatusSave($urma, $rmaStatus, true, $vendor);
                if ($rmaStatusChanged) {
                    $session->addSuccess($this->__('RMA status has been changed'));
                } else {
                    $session->addError($this->__('Cannot change RMA status'));
                }
            }

            $is_customer_notified = $r->getParam('is_customer_notified');
            $is_visible_on_front = $r->getParam('is_visible_on_front');
            if ($is_customer_notified) {
                $is_visible_on_front = true;
            }

            $comment = $r->getParam('comment');
            $resolutionNotes = $r->getParam('resolution_notes');
            if ($comment || $partial=='inform' && $partialQty || $is_customer_notified && $resolutionNotes) {
                if ($partialQty) {
                    $comment .= "\n\nPartial Availability:\n";
                    foreach ($urma->getAllItems() as $item) {
                        if (empty($partialQty[$item->getId()])) {
                            continue;
                        }
                        $comment .= $this->__('%s x [%s] %s', $partialQty[$item->getId()], $item->getName(), $item->getSku())."\n";
                    }
                }

                if ($resolutionNotes!==null) {
                    $urma->setResolutionNotes($resolutionNotes);
                    $urma->getResource()->saveAttribute($urma, 'resolution_notes');
                }

                Mage::helper('urma')->sendVendorComment($urma, $comment, $is_customer_notified, $is_visible_on_front);
                $session->addSuccess($this->__('Your comment has been sent'));

                $highlight['comment'] = true;
            }

            $deleteTrack = $r->getParam('delete_track');
            if ($deleteTrack) {
                $track = Mage::getModel('urma/rma_track')->load($deleteTrack);
                if ($track->getId()) {
                    $track->delete();
                    if ($track->getPackageCount()>1) {
                        foreach (Mage::getResourceModel('urma/rma_track_collection')
                            ->addAttributeToFilter('master_tracking_id', $track->getMasterTrackingId())
                            as $_track
                        ) {
                            $_track->delete();
                        }
                    }
                    $urma->addComment(
                        $this->__('%s added tracking ID %s', $vendor->getVendorName(), $number),
                        false, true
                    );
                    $urma->saveComments();
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

        $this->_forward('urmaInfo');
    }
    
    public function updateUrmaStatusAction()
    {
        try {
            $urmas = $this->getVendorRmaCollection();
            $r = $this->getRequest();
            $rmaStatus = $this->getRequest()->getParam('update_status');

            if (!$urmas->getSize()) {
                Mage::throwException($this->__('No RMAs found for these criteria'));
            }
            if (is_null($rmaStatus) || $rmaStatus==='') {
                Mage::throwException($this->__('No status selected'));
            }

            $vendorId = $this->_getSession()->getId();
            $vendor = Mage::helper('udropship')->getVendor($vendorId);

            $hlp = Mage::helper('udropship');
            $urmaHlp = Mage::helper('urma');

            foreach ($urmas as $urma) {
                if (!is_null($rmaStatus) && $rmaStatus!=='' && $rmaStatus!=$urma->getRmaStatus()) {
                    $urmaHlp->processRmaStatusSave($urma, $rmaStatus, true, $vendor);
                }
            }
            $this->_getSession()->addSuccess($this->__('RMA status has been updated'));
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__($e->getMessage()));
        }
        $this->_redirect('urma/vendor/', array('_current'=>true, '_query'=>array('submit_action'=>'')));
    }
    
    public function getVendorRmaCollection()
    {
        return Mage::helper('urma')->getVendorRmaCollection();
    }
    
}
