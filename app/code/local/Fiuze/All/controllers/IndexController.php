<?php

/**
 *
 * @category   Fiuze
 * @package    Fiuze_All
 * @author     Webinse Team <info@webinse.com>
 */
class Fiuze_All_IndexController extends Mage_Core_Controller_Front_Action{

    public function indexAction(){
       echo'test_fuize_cron';
    //    Mage::getModel('track/observer')->notificationMail();
//        Mage::getModel('aftershiptrackcron/observer')->changeStatusShip('a');
        Mage::getModel('udbatch/observer')->processStandard();
    }
}