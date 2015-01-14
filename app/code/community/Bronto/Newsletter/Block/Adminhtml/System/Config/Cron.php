<?php

/**
 * @package     Bronto\Newsletter
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Newsletter_Block_Adminhtml_System_Config_Cron extends Bronto_Common_Block_Adminhtml_System_Config_Cron
{
    protected $_jobCode = 'bronto_newsletter_import';
    protected $_hasProgressBar = true;

    /**
     * @return Bronto_Order_Block_Adminhtml_System_Config_Cron
     */
    protected function _prepareLayout()
    {
        $this->addButton($this->getLayout()->createBlock('bronto_newsletter/adminhtml_widget_button_sync'));
        $this->addButton($this->getLayout()->createBlock('bronto_newsletter/adminhtml_widget_button_reset'));
        $this->addButton($this->getLayout()->createBlock('bronto_newsletter/adminhtml_widget_button_mark'));
        $this->addButton($this->getLayout()->createBlock('bronto_newsletter/adminhtml_widget_button_run'));

        return parent::_prepareLayout();
    }

    /**
     * @return int
     */
    protected function getProgressBarTotal()
    {
        return $this->getNewsletterResourceCollection()
            ->getSize();
    }

    /**
     * @return int
     */
    protected function getProgressBarPending()
    {
        return $this->getNewsletterResourceCollection()
            ->addBrontoNotImportedFilter()
            ->addBrontoNotSuppressedFilter()
            ->getSize();
    }

    /**
     * @return int
     */
    protected function getProgressBarSuppressed()
    {
        return $this->getNewsletterResourceCollection()
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
        $collection = Mage::getModel('bronto_newsletter/queue')->getCollection();
        $storeIds   = Mage::helper('bronto_newsletter')->getStoreIds();

        if ($storeIds) {
            if (!is_array($storeIds)) {
                $storeIds = array($storeIds);
            }
            foreach ($storeIds as $key => $storeId) {
                if (Mage::getStoreConfig(Bronto_Newsletter_Helper_Data::XML_PATH_ENABLED, $storeId)) {
                    unset($storeIds[$key]);
                }
            }

            if (count($storeIds) > 0) {
                $collection->addStoreFilter($storeIds);

                return $collection->addBrontoNotImportedFilter()
                    ->getSize();
            }
        }

        return 0;
    }

    /**
     * @return Bronto_Newsletter_Model_Mysql4_Queue_Collection
     */
    protected function getNewsletterResourceCollection()
    {
        $collection = Mage::getModel('bronto_newsletter/queue')->getCollection();
        $storeIds   = Mage::helper('bronto_newsletter')->getStoreIds();

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
        return Mage::helper('bronto_newsletter')->canUseMageCron();
    }
}
