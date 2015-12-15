<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Block_Adminhtml_System_Config_Cron extends Bronto_Common_Block_Adminhtml_System_Config_Cron
{
    /**
     * @var string
     */
    protected $_jobCode = 'bronto_order_import';

    /**
     * @var boolean
     */
    protected $_hasProgressBar = true;

    /**
     * @return Bronto_Order_Block_Adminhtml_System_Config_Cron
     */
    protected function _prepareLayout()
    {
        $this->addButton($this->getLayout()->createBlock('bronto_order/adminhtml_widget_button_sync'));
        $this->addButton($this->getLayout()->createBlock('bronto_order/adminhtml_widget_button_reset'));
        $this->addButton($this->getLayout()->createBlock('bronto_order/adminhtml_widget_button_mark'));
        $this->addButton($this->getLayout()->createBlock('bronto_order/adminhtml_widget_button_run'));

        return parent::_prepareLayout();
    }

    /**
     * @return int
     */
    protected function getProgressBarTotal()
    {
        return $this->getOrderResourceCollection()
            ->addBrontoHasOrderFilter()
            ->getSize();
    }

    /**
     * @return int
     */
    protected function getProgressBarPending()
    {
        return $this->getOrderResourceCollection()
            ->addBrontoNotImportedFilter()
            ->addBrontoNotSuppressedFilter()
            ->addBrontoHasOrderFilter()
            ->getSize();
    }

    /**
     * @return int
     */
    protected function getProgressBarSuppressed()
    {
        return $this->getOrderResourceCollection()
            ->addBrontoNotImportedFilter()
            ->addBrontoSuppressedFilter()
            ->getSize();
    }

    /**
     * Get number of customers not imported from stores that don't have module enabled
     *
     * @return int
     */
    protected function getProgressBarDisabled()
    {
        $collection = Mage::getModel('bronto_order/queue')->getCollection();
        $storeIds   = Mage::helper('bronto_order')->getStoreIds();

        if ($storeIds) {
            if (!is_array($storeIds)) {
                $storeIds = array($storeIds);
            }
            foreach ($storeIds as $key => $storeId) {
                if (Mage::getStoreConfig(Bronto_Order_Helper_Data::XML_PATH_ENABLED, $storeId)) {
                    unset($storeIds[$key]);
                }
            }
            $collection->addStoreFilter($storeIds);

            return $collection->addBrontoNotImportedFilter()
                ->addBrontoNotSuppressedFilter()
                ->getSize();

        }

        return 0;
    }

    /**
     * @return Bronto_Order_Model_Mysql4_Queue_Collection
     */
    protected function getOrderResourceCollection()
    {
        $collection = Mage::getModel('bronto_order/queue')->getCollection();
        $storeIds   = Mage::helper('bronto_order')->getStoreIds();

        if ($storeIds) {
            $collection->addStoreFilter($storeIds);
        }

        return $collection;
    }

    /**
     * Determine if should show the cron table
     *
     * @return mixed
     */
    public function showCronTable()
    {
        return Mage::helper('bronto_order')->canUseMageCron();
    }
}
