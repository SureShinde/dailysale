<?php


class Fiuze_Notifylowstock_CronController extends Mage_Core_Controller_Front_Action
{
    public function cronSendAction()
    {
        if(Mage::helper('fiuze_notifylowstock')->getModuleEnabled()){
            $cronModel = Mage::getModel('fiuze_notifylowstock/cron');
            $products = $cronModel->getNotifyLowStockCategory();

            $cronModel->sendEmail($products);
        }
    }
} 