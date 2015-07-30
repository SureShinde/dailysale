<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 27.07.15
 * Time: 18:07
 */

class Fiuze_AddressValidation_AjaxController extends Mage_Core_Controller_Front_Action {

    public function billing_ajaxAction(){

        $address[0] = $this->getRequest()->getParam('0');
        $address[1] = $this->getRequest()->getParam('1');
        $address[2] = $this->getRequest()->getParam('2');
        $address[3] = $this->getRequest()->getParam('3');
        $address[4] = $this->getRequest()->getParam('4');

        $result = Mage::getModel('fiuze_addressvalidation/addresses')->checkAddress($address);
        $this->getResponse()->setBody($result);

    }
}