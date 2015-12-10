<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Adminhtml_System_Email_LogController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->_title($this->__('Bronto Deliveries'))->_title($this->__('Logs'));
        $this->loadLayout()
            ->_setActiveMenu('system/email/log')
            ->_addBreadcrumb(
                Mage::helper('bronto_email')->__('Bronto Delivery Log'),
                Mage::helper('bronto_email')->__('Bronto Delivery Log')
            );

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    /**
     * Deletes all log history
     */
    public function clearAction()
    {
        /* @var $collection Bronto_Email_Model_Mysql4_Log_Collection */
        $collection = Mage::getModel('bronto_email/log')->getCollection();
        $collection->delete();
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('bronto_email')->__('All log entries have been deleted'));
        $this->_redirect('*/*/index');
    }
}
