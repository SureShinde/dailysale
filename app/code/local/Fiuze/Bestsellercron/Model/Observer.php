<?php
class Fiuze_Bestsellercron_Model_Observer
{
    public function coreBlockAbstractPrepareLayoutAfter(Varien_Event_Observer $observer)
    {
        if($observer->getEvent()->getName() != 'admin_system_config_changed_section_bestsellers_settings_sec'){
            return;
        }
        Mage::app()->getCacheInstance()->cleanType('config');
    }

    public function controllerFrontInitBefore(Varien_Event_Observer $observer)
    {
        if($observer->getEvent()->getName() != 'admin_system_config_changed_section_bestsellers_settings_sec'){
            return;
        }
        Mage::app()->getCacheInstance()->cleanType('config');
    }
    public function catalogProductCollectionLoadAfter(Varien_Event_Observer $observer){
        //$productCollection = $observer->getData('collection');
        //foreach($productCollection as $item){

        //}
        //catalog_block_product_list_collection
    }
}