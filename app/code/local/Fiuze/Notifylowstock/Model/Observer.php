<?php
class Fiuze_Notifylowstock_Model_Observer{
    public function changeFlagNotify($data){

        $fiuze_notif = Mage::getResourceModel('catalog/product')->getAttributeRawValue(
            $data->getItem()->getProductId(),
            'fiuze_lowstock_notif',
            Mage::app()->getStore()->getId()
        );
        $fiuze_lowstock_flag = Mage::getResourceModel('catalog/product')->getAttributeRawValue(
            $data->getItem()->getProductId(),
            'fiuze_lowstock_flag',
            Mage::app()->getStore()->getId()
        );
        if($fiuze_lowstock_flag==1){
            $ls = Mage::getResourceModel('catalog/product')->getAttributeRawValue(
                $data->getItem()->getProductId(),
                'fiuze_lowstock_qty',
                Mage::app()->getStore()->getId()
            );
        }else{
            $ls = Mage::helper('fiuze_notifylowstock')->getQuantity();
        }
        if($data->getItem()->getQty()>$ls AND $fiuze_notif==1){
            Mage::getModel('catalog/product')->load($data->getItem()->getProductId())->setFiuzeLowstockNotif('0')->save();
        }
    }

    /*public function udropship_vendor_preferences_save_before_handler($data){
        $notify_lowstock = $data->getVendor()->getNotifyLowstock();
        $notify_lowstock_qty = $data->getVendor()->getNotifyLowstockQty();
        Mage::getModel('core/config')->saveConfig('notifylowstock/core/module_enabled', $notify_lowstock);
        Mage::getModel('core/config')->saveConfig('notifylowstock/core/quantity', (int)$notify_lowstock_qty);
    }*/

    public function admin_system_config_changed_section_notifylowstock_handler()
    {
        /*$notify_lowstock = Mage::getStoreConfig('notifylowstock/core/module_enabled');
        $notify_lowstock_qty = Mage::getStoreConfig('notifylowstock/core/quantity');
        $vendorsCollection = Mage::getModel('udropship/vendor')->getCollection();
        foreach ($vendorsCollection as $vendor) {
            $vendor->setData('notify_lowstock', $notify_lowstock)->save();
            $vendor->setData('notify_lowstock_qty', $notify_lowstock_qty)->save();
        }*/
    }
}