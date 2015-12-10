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

class Unirgy_DropshipBatch_Adminhtml_BatchController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('udbatch/adminhtml_batch'));
        $this->renderLayout();
    }

    public function editAction()
    {
        $this->loadLayout();

        $this->_setActiveMenu('sales/udropship');
        $this->_addBreadcrumb(Mage::helper('udbatch')->__('Vendor PO Import/Export Batches'), Mage::helper('udropship')->__('Vendor PO Import/Export Batches'));

        $this->_addContent($this->getLayout()->createBlock('udbatch/adminhtml_batch_edit'))
            ->_addLeft($this->getLayout()->createBlock('udbatch/adminhtml_batch_edit_tabs'));

        $this->renderLayout();
    }

    public function newAction()
    {
        $this->editAction();
    }


    public function newImportAction()
    {
        $this->editAction();
    }

    public function saveAction()
    {
        if ( $this->getRequest()->getPost() ) {
            $r = $this->getRequest();
            $hlp = Mage::helper('udbatch');
            try {
                $hlp->useCustomTemplate($r->getParam('use_custom_template'));
                $hlp->processPost();
                $hlp->useCustomTemplate('');
                Mage::getSingleton('adminhtml/session')->addSuccess($hlp->__('Batch was successfully saved'));

                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                $hlp->useCustomTemplate('');
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
                $model = Mage::getModel('udbatch/batch');
                /* @var $model Unirgy_DropshipBatch_Model_Batch */
                $model->setId($id)->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('udbatch')->__('Batch was successfully deleted'));
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
            $this->getLayout()->createBlock('udbatch/adminhtml_batch_grid')->toHtml()
        );
    }

    /**
     * Export subscribers grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'batches.csv';
        $content    = $this->getLayout()->createBlock('udbatch/adminhtml_batch_grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export subscribers grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName   = 'batches.xml';
        $content    = $this->getLayout()->createBlock('udbatch/adminhtml_batch_grid')
            ->getXml();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function massDeleteAction()
    {
        $batchIds = $this->getRequest()->getParam('batch');
        if (!is_array($batchIds)) {
            $this->_getSession()->addError($this->__('Please select batch(es)'));
        }
        else {
            try {
                $cert = Mage::getSingleton('udbatch/batch');
                foreach ($batchIds as $batchId) {
                    $cert->setId($batchId)->delete();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully deleted', count($batchIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massStatusAction()
    {
        $modelIds = (array)$this->getRequest()->getParam('batch');
        $status     = (string)$this->getRequest()->getParam('status');

        try {
            $model = Mage::getSingleton('udbatch/batch');
            foreach ($modelIds as $modelId) {
                $model->setId($modelId)->setStatus($status)->save();
            }
            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) were successfully updated', count($modelIds))
            );
        }
        catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('There was an error while updating batch(es) status'));
        }

        $this->_redirect('*/*/');
    }

    public function distGridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udbatch/adminhtml_batch_edit_tab_dist', 'admin.udbatch.dist')
                ->setBatchId($this->getRequest()->getParam('id'))
                ->toHtml()
        );
    }

    public function rowGridAction()
    {
        $blockType = $this->getRequest()->getParam('type')=='export_orders'
            ? 'udbatch/adminhtml_batch_edit_tab_export_rows'
            : 'udbatch/adminhtml_batch_edit_tab_import_rows';
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock($blockType, 'admin.udbatch.rows')
                ->setBatchId($this->getRequest()->getParam('id'))
                ->toHtml()
        );
    }

    public function vendorBatchGridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udbatch/adminhtml_vendor_tab_batches', 'admin.udbatch.rows')
                ->setVendorId($this->getRequest()->getParam('id'))
                ->toHtml()
        );
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/udropship/batch');
    }
}
