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

class Unirgy_DropshipPo_Adminhtml_ReportController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();

        $hlp = Mage::helper('udropship');

        $this->_setActiveMenu('report/udropship/advanced');
        $this->_addBreadcrumb($hlp->__('Report'), $hlp->__('Report'));
        $this->_addContent($this->getLayout()->createBlock('udpo/adminhtml_report'));

        $this->renderLayout();
    }

    public function itemAction()
    {
        $this->loadLayout();

        $hlp = Mage::helper('udropship');

        $this->_setActiveMenu('report/udropship/advanced_item');
        $this->_addBreadcrumb($hlp->__('Report'), $hlp->__('Report'));
        $this->_addContent($this->getLayout()->createBlock('udpo/adminhtml_reportItem'));

        $this->renderLayout();
    }
    
    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'grid':
            case 'exportCsv':
            case 'exportXml':
                return Mage::getSingleton('admin/session')->isAllowed('report/udropship/advanced');
            case 'item':
            case 'itemGrid':
            case 'itemExportCsv':
            case 'itemExportXml':
                return Mage::getSingleton('admin/session')->isAllowed('report/udropship/advanced_item');
        }
        return parent::_isAllowed();
    }

    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udpo/adminhtml_report_grid')->toHtml()
        );
    }

    public function itemGridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udpo/adminhtml_reportItem_grid')->toHtml()
        );
    }
    
    public function exportCsvAction()
    {
        $fileName   = 'advanced_report.csv';
        $content    = $this->getLayout()->createBlock('udpo/adminhtml_report_grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'advanced_report.xml';
        $content    = $this->getLayout()->createBlock('udpo/adminhtml_report_grid')
            ->getXml();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function itemExportCsvAction()
    {
        $fileName   = 'advanced_item_report.csv';
        $content    = $this->getLayout()->createBlock('udpo/adminhtml_reportItem_grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function itemExportXmlAction()
    {
        $fileName   = 'advanced_item_report.xml';
        $content    = $this->getLayout()->createBlock('udpo/adminhtml_reportItem_grid')
            ->getXml();

        $this->_prepareDownloadResponse($fileName, $content);
    }
}
