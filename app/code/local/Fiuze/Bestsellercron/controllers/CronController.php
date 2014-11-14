<?php

class Fiuze_Bestsellercron_CronController extends Mage_Core_Controller_Front_Action
{
    public function updateBestsellersAction()
    {
        $model = Mage::getModel('bestsellercron/calculate');
        $model->prepareBestsellers();
    }
}