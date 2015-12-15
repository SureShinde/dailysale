<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Adminhtml_DeliveryController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->_title($this->__('Bronto Deliveries'))->_title($this->__('Logs'));
        $this->loadLayout()
            ->_setActiveMenu('adminhtml_delivery_index')
            ->_addBreadcrumb(
                Mage::helper('bronto_reminder')->__('Bronto Delivery Log'),
                Mage::helper('bronto_reminder')->__('Bronto Delivery Log')
            );

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    /**
     * Override for ACL permissions
     */
    protected function _isAllowed()
    {
        $session = Mage::getSingleton('admin/session');
        return $session->isAllowed('admin/promo/bronto_reminder');
    }

    /**
     * Deletes all log history
     */
    public function clearAction()
    {
        /* @var $collection Bronto_Reminder_Model_Mysql4_Delivery_Collection */
        $collection = Mage::getModel('bronto_reminder/delivery')->getCollection();
        $collection->delete();
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('bronto_reminder')->__('All log entries have been deleted'));
        $this->_redirect('*/*/index');
    }
}
