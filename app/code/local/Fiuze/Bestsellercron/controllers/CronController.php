<?php

class Fiuze_Bestsellercron_CronController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $rowId = $this->getRequest()->getPost('rowId');
        if(!is_null($rowId)){
            $model = Mage::getModel('bestsellercron/cron');
            $model->bestSellers($rowId);
        }
    }
    public function customcronAction(){
        Mage::getModel('bestsellercron/customcron')->runCustomCron();
    }
}