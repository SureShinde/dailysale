<?php
class Fiuze_AddressValidation_Model_Observer{
    public function addDpvtoorder($data){
        $data->getEvent()->getOrder()->setDvpshippingid($data->getEvent()->getOrder()->getQuote()->getDvpshippingid())->save();
        $data->getEvent()->getOrder()->setDvpbillingid($data->getEvent()->getOrder()->getQuote()->getDvpbillingid())->save();
    }
}