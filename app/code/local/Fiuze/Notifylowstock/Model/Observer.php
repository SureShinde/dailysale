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
}