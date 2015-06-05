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

class Unirgy_DropshipBatch_Adminhtml_DistController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('udbatch/adminhtml_dist'));
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udbatch/adminhtml_dist_grid')->toHtml()
        );
    }

    /**
     * Export subscribers grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'dist_history.csv';
        $content    = $this->getLayout()->createBlock('udbatch/adminhtml_dist_grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export subscribers grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName   = 'dist_history.xml';
        $content    = $this->getLayout()->createBlock('udbatch/adminhtml_dist_grid')
            ->getXml();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function massStatusAction()
    {
        $modelIds = (array)$this->getRequest()->getParam('dist');
        $status     = (string)$this->getRequest()->getParam('status');

        try {
            $model = Mage::getSingleton('udbatch/batch_dist');
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
            $this->_getSession()->addException($e, $this->__('There was an error while updating history(ies) status'));
        }

        $this->_redirect('*/*/');
    }

    public function massRetryAction()
    {
        $modelIds = (array)$this->getRequest()->getParam('dist');
        try {
            Mage::helper('udbatch')->retryDists($modelIds);
            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) were retried', count($modelIds))
            );
        }
        catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('There was an error while retrying distribution: '.$e->getMessage()));
        }

        $this->_redirect('*/*/');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/udropship/batch');
    }
}
