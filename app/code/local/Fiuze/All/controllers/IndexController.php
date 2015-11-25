<?php

/**
 * Test controller
 *
 * @category   Fiuze
 * @package    Fiuze_All
 * @author     Webinse Team <info@webinse.com>
 */
class Fiuze_All_IndexController extends Mage_Core_Controller_Front_Action{

    public function indexAction(){
        Mage::getModel('bestsellercron/customcron')->runCustomCron();
    }
}