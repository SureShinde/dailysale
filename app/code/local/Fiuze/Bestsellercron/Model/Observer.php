<?php
class Fiuze_Bestsellercron_Model_Observer
{
    public function adminSystemConfigChangedBestsellersSettingsSec(Varien_Event_Observer $observer)
    {
        if($observer->getEvent()->getName() != 'admin_system_config_changed_section_bestsellers_settings_sec'){
            return;
        }
        Mage::app()->getCacheInstance()->cleanType('config');
    }
}