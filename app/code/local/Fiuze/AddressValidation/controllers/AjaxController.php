<?php

class Fiuze_AddressValidation_AjaxController extends Mage_Core_Controller_Front_Action {

    public function billing_ajaxAction(){

        $address[0] = $this->getRequest()->getParam('1');
        $address[1] = $this->getRequest()->getParam('2');
        $address[2] = $this->getRequest()->getParam('3');
        $address[3] = $this->getRequest()->getParam('4');
        $address[4] = $this->getRequest()->getParam('5');
        if($address['2']==null){
            $address = Mage::getModel('fiuze_addressvalidation/addresses')->getCustomerAddress($address);
        }
        $address['tipe']=$this->getRequest()->getParam('0');
        $result = Mage::getModel('fiuze_addressvalidation/addresses')->checkAddress($address);
        $this->getResponse()->setBody($result);

    }
    public function getAddressCustomerAction(){
        if ($this->getRequest()->getParam('0')==''){
            return;
        }
        $this->getResponse()->setBody(
            json_encode(array('result'=>Mage::getModel('fiuze_addressvalidation/addresses')->getCustomerAddress(array('0' => $this->getRequest()->getParam('0')))))
            );
    }
}