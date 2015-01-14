<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Customer_Block_Adminhtml_System_Config_Cron extends Bronto_Common_Block_Adminhtml_System_Config_Cron
{
    protected $_jobCode = 'bronto_customer_import';
    protected $_hasProgressBar = true;

    /**
     * @return Bronto_Order_Block_Adminhtml_System_Config_Cron
     */
    protected function _prepareLayout()
    {
        $this->addButton($this->getLayout()->createBlock('bronto_customer/adminhtml_widget_button_sync'));
        $this->addButton($this->getLayout()->createBlock('bronto_customer/adminhtml_widget_button_reset'));
        $this->addButton($this->getLayout()->createBlock('bronto_customer/adminhtml_widget_button_mark'));
        $this->addButton($this->getLayout()->createBlock('bronto_customer/adminhtml_widget_button_run'));

        return parent::_prepareLayout();
    }

    /**
     * @return int
     */
    protected function getProgressBarTotal()
    {
        return $this->getCustomerResourceCollection()
            //            ->addBrontoNotSuppressedFilter()
            ->getSize();
    }

    /**
     * @return int
     */
    protected function getProgressBarPending()
    {
        return $this->getCustomerResourceCollection()
            ->addBrontoNotImportedFilter()
            ->addBrontoNotSuppressedFilter()
            ->getSize();
    }

    /**
     * @return int
     */
    protected function getProgressBarSuppressed()
    {
        return $this->getCustomerResourceCollection()
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
        $collection = Mage::getModel('bronto_customer/queue')->getCollection();
        $storeIds   = Mage::helper('bronto_customer')->getStoreIds();

        if ($storeIds) {
            if (!is_array($storeIds)) {
                $storeIds = array($storeIds);
            }
            foreach ($storeIds as $key => $storeId) {
                if (Mage::getStoreConfig(Bronto_Customer_Helper_Data::XML_PATH_ENABLED, $storeId)) {
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
     * @return Bronto_Customer_Model_Mysql4_Queue_Collection
     */
    protected function getCustomerResourceCollection()
    {
        $collection = Mage::getModel('bronto_customer/queue')->getCollection();
        $storeIds   = Mage::helper('bronto_customer')->getStoreIds();

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
        return Mage::helper('bronto_customer')->canUseMageCron();
    }
}
