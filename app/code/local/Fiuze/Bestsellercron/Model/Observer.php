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
        $_category = Mage::registry('current_category');
        if(is_null($_category)){
            return true;
        }
        $bestSellerCategoryConfig = Mage::getModel('bestsellercron/system_config_backend_general')
            ->load(Fiuze_Bestsellercron_Model_Cron::XML_PATH_CATEGORY_FORM, 'path');
        foreach($bestSellerCategoryConfig->getValue() as $key => $item){
            if(Mage::getStoreConfig(Fiuze_Bestsellercron_Model_Bestsellers::XML_PATH_BESTSELLER_ROWID) == $item['category']){
                return ;
            }
            if($_category->getId() == $item['category']){
                $countProduct = $item['number_of_products'];
                if($countProduct){
                    $productCollection = $observer->getData('collection');
                    $productCollection->setPageSize($countProduct);
                }
            }
        }
        return true;
    }

}