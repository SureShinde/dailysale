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
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipMicrosite_Umicrositeadmin_RegistrationController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();

        $hlp = Mage::helper('umicrosite');

        $this->_setActiveMenu('sales/udropship');
        $this->_addBreadcrumb(Mage::helper('udropship')->__('Vendor Registrations'), Mage::helper('udropship')->__('Vendor Registrations'));
        $this->_addContent($this->getLayout()->createBlock('umicrosite/adminhtml_registration'));

        $this->renderLayout();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/udropship/vendor_registration');
    }

    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('umicrosite/adminhtml_registration_grid')->toHtml()
        );
    }
    /**
     * Export subscribers grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'registrations.csv';
        $content    = $this->getLayout()->createBlock('umicrosite/adminhtml_registration_grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export subscribers grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName   = 'registrations.xml';
        $content    = $this->getLayout()->createBlock('umicrosite/adminhtml_registration_grid')
            ->getXml();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function massDeleteAction()
    {
        $certIds = $this->getRequest()->getParam('vendor');
        if (!is_array($certIds)) {
            $this->_getSession()->addError(Mage::helper('udropship')->__('Please select registration(s)'));
        }
        else {
            try {
                $cert = Mage::getSingleton('umicrosite/registration');
                foreach ($certIds as $certId) {
                    $cert->setId($certId)->delete();
                }
                $this->_getSession()->addSuccess(
                    Mage::helper('udropship')->__('Total of %d record(s) were successfully deleted', count($certIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('reg_id');
        $reg = Mage::getModel('umicrosite/registration')->load($id);
        if (!$reg) {
            return;
        }
        Mage::register('vendor_data', $reg->toVendor());

        $this->_forward('edit', 'udropshipadmin_vendor', Mage::app()->getFrontController()->getRouterByRoute('adminhtml')->getFrontNameByRoute('adminhtml'));
        return;
        $this->loadLayout();

        $this->_setActiveMenu('sales/udropship');
        $this->_addBreadcrumb(Mage::helper('udropship')->__('Vendor Registrations'), Mage::helper('udropship')->__('Vendor Registrations'));

        $this->_addContent($this->getLayout()->createBlock('udropship/adminhtml_vendor_edit'))
            ->_addLeft($this->getLayout()->createBlock('udropship/adminhtml_vendor_edit_tabs'));

        $this->renderLayout();
    }
}
