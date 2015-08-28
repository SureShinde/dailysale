<?php
class Fiuze_AddressValidation_Model_Observer{
    public function addDpvtoorder($data){
        $data->getEvent()->getOrder()->setDvpid($data->getEvent()->getOrder()->getQuote()->getDvpid())->save();
    }
}