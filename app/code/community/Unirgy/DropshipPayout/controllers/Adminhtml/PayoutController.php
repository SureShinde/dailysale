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
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipPayout_Adminhtml_PayoutController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('udpayout/adminhtml_payout'));
        $this->renderLayout();
    }

    public function editAction()
    {
        $this->loadLayout();

        $this->_setActiveMenu('sales/udropship');
        $this->_addBreadcrumb(Mage::helper('udpayout')->__('Payouts'), Mage::helper('udpayout')->__('Payouts'));

        $this->_addContent($this->getLayout()->createBlock('udpayout/adminhtml_payout_edit'))
            ->_addLeft($this->getLayout()->createBlock('udpayout/adminhtml_payout_edit_tabs'));

        $this->renderLayout();
    }

    public function newAction()
    {
        $this->editAction();
    }

    public function saveAction()
    {
        if ( $this->getRequest()->getPost() ) {
            $r = $this->getRequest();
            $hlp = Mage::helper('udpayout');
            try {
                if (($id = $this->getRequest()->getParam('id')) > 0) {
                    if (($payout = Mage::getModel('udpayout/payout')->load($id)) && $payout->getId()) {
                        $payout->setNotes($this->getRequest()->getParam('notes'));
                        if (($adjArr = $this->getRequest()->getParam('adjustment'))
                            && is_array($adjArr) && is_array($adjArr['amount'])
                        ) {
                            foreach ($adjArr['amount'] as $k => $adjAmount) {
                                if (is_numeric($adjAmount)) {
                                    $createdAdj = $payout->createAdjustment($adjAmount)
                                        ->setComment(isset($adjArr['comment'][$k]) ? $adjArr['comment'][$k] : '')
                                        ->setPoType(isset($adjArr['po_type'][$k]) ? $adjArr['po_type'][$k] : null)
                                        ->setUsername(Mage::getSingleton('admin/session')->getUser()->getUsername())
                                        ->setPoId(isset($adjArr['po_id'][$k]) ? $adjArr['po_id'][$k] : null);
                                    $payout->addAdjustment($createdAdj);
                                }
                            }
                            $payout->finishPayout();
                        }
                        $payout->save();
                        if ($this->getRequest()->getParam('pay_flag')) {
                            $payout->pay();
                            Mage::getSingleton('adminhtml/session')->addSuccess($hlp->__('Payout was successfully paid'));
                        }
                    } else {
                        Mage::throwException($hlp->__("Payout '%s' no longer exists", $id));
                    }
                } else {
                    $hlp->processPost();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess($hlp->__('Payout was successfully saved'));

                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if (($id = $this->getRequest()->getParam('id')) > 0 ) {
            try {
                $model = Mage::getModel('udpayout/payout');
                /* @var $model Unirgy_DropshipPayout_Model_Payout */
                $model->load($id)->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('udpayout')->__('Payout was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function cancelAction()
    {
        if (($id = $this->getRequest()->getParam('id')) > 0 ) {
            try {
                if (($id = $this->getRequest()->getParam('id')) > 0
                    && ($payout = Mage::getModel('udpayout/payout')->load($id)) && $payout->getId()
                ) {
                    $payout->cancel();
                } else {
                    Mage::throwException($hlp->__("Payout '%s' no longer exists", $id));
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('udpayout')->__('Payout was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('core/template', 'formkey')
                ->setTemplate('formkey.phtml')
                ->toHtml()
            .
            $this->getLayout()->createBlock('udpayout/adminhtml_payout_grid')->toHtml()
        );
    }

    public function exportCsvAction()
    {
        $fileName   = 'payouts.csv';
        $content    = $this->getLayout()->createBlock('udpayout/adminhtml_payout_grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'payouts.xml';
        $content    = $this->getLayout()->createBlock('udpayout/adminhtml_payout_grid')
            ->getXml();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function massDeleteAction()
    {
        $payoutIds = $this->getRequest()->getParam('payout');
        if (!is_array($payoutIds)) {
            $this->_getSession()->addError($this->__('Please select payout(s)'));
        }
        else {
            try {
                $updatedCnt = 0;
                foreach ($payoutIds as $payoutId) {
                    if (($payout = Mage::getModel('udpayout/payout')->load($payoutId)) && $payout->getId()) {
                        $payout->delete();
                        $updatedCnt++;
                    }
                }
                $this->_getSession()->addSuccess(
                    $this->__('%s of %d record(s) were successfully deleted', $updatedCnt, count($payoutIds))
                );
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massStatusAction()
    {
        $payoutIds = (array)$this->getRequest()->getParam('payout');
        $status     = (string)$this->getRequest()->getParam('status');

        try {
            $updatedCnt = 0;
            foreach ($payoutIds as $payoutId) {
                if (($payout = Mage::getModel('udpayout/payout')->load($payoutId)) && $payout->getId()) {
                    if ($status == Unirgy_DropshipPayout_Model_Payout::STATUS_CANCELED) {
                        $payout->cancel();
                    } else {
                        $payout->setPayoutStatus($status)->save();
                    }
                    $updatedCnt++;
                }
            }
            $this->_getSession()->addSuccess(
                $this->__('%s of %d record(s) were successfully updated', $updatedCnt, count($payoutIds))
            );
        }
        catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('There was an error while updating payout(s) status'));
        }

        $this->_redirect('*/*/');
    }

    public function massPayAction()
    {
        $modelIds = (array)$this->getRequest()->getParam('payout');
        $status     = (string)$this->getRequest()->getParam('status');

        try {
            $ptCollection = Mage::getResourceModel('udpayout/payout_collection')->addFieldToFilter('payout_id', $modelIds);
            $ptCollection->pay();
            $paidCnt = 0;
            foreach ($ptCollection as $pt) {
                if ($pt->getIsJustPaid()) $paidCnt += 1;
            }
            $this->_getSession()->addSuccess(
                $this->__('%s of %s payouts  were successfully paid', $paidCnt, count($modelIds))
            );
        }
        catch (Mage_Core_Model_Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addException($e, $this->__('There was an error during payout(s) mass pay'));
        }

        $this->_redirect('*/*/');
    }

    public function rowGridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udpayout/adminhtml_payout_edit_tab_rows', 'admin.udpayout.rows')
                ->setPayoutId($this->getRequest()->getParam('id'))
                ->toHtml()
        );
    }

    public function adjustmentGridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udpayout/adminhtml_payout_edit_tab_adjustments', 'admin.udpayout.adjustments')
                ->setPayoutId($this->getRequest()->getParam('id'))
                ->toHtml()
        );
    }

    public function vendorPayoutsGridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udpayout/adminhtml_vendor_payout_grid', 'admin.udpayout.rows')
                ->setVendorId($this->getRequest()->getParam('id'))
                ->toHtml()
        );
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/udropship/payouts');
    }
}
