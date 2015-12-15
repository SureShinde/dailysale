<?php

class Fiuze_TowerData_IndexController extends Mage_Core_Controller_Front_Action {

    public function ajaxAction()
    {
        $email = $this->getRequest()->getParam('email');
        $result = Mage::getSingleton('fiuze_towerdata/api')->callApiMail($email);

        $this->getResponse()->setBody(Zend_Json::encode($result));
    }
} 