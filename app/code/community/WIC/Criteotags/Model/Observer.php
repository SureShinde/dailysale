<?php

class WIC_Criteotags_Model_Observer {

    public function cron($observer) {

        $_helper = Mage::helper('criteotags');

        $store_collection = Mage::getResourceModel('core/store_collection')
                ->addFieldToFilter('is_active', 1);
        
    //$exceptions = array();

        foreach ($store_collection as $store) {
            try {
                Mage::app()->setCurrentStore($store);

                if ($_helper->isExportEnable()) {

                    $feed = Mage::getSingleton('criteotags/export_xml');
                    $feed->runExport();
                }
            } catch (Exception $e) {
                $_helper->log($e->getMessage());
                $_helper->log($e->getTraceAsString());
            }
        }
        return $this;
    }

}
