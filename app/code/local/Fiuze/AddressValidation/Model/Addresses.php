<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 27.07.15
 * Time: 18:05
 */ 
class Fiuze_AddressValidation_Model_Addresses extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('fiuze_addressvalidation/addresses');
    }

    public function checkAddress($param){
        if($param['1']=='US') {

            if(Mage::getStoreConfig('fiuze_address_validation/license_group/wsdl_input', Mage::app()->getStore())==""){
                $wsdlUrl = 'https://trial.serviceobjects.com/AV3/api.svc?wsdl';
            }else {
                $wsdlUrl = Mage::getStoreConfig('fiuze_address_validation/license_group/wsdl_input', Mage::app()->getStore());
            }
            $data['Business'] = Mage::getStoreConfig('fiuze_address_validation/license_group/name_input', Mage::app()->getStore());
            $data['Address'] = $param['0'];
            $data['Address2'] = '';
            $data['City'] = $param['2'];
            $data['State'] = $param['4'];
            $data['PostalCode'] = $param['3'];
            $data['LicenseKey'] = Mage::getStoreConfig('fiuze_address_validation/license_group/license_input', Mage::app()->getStore());

            $matches = $this->getCollection()->
            addFIeldToFilter('address',$data['Address'])->
            addFIeldToFilter('city',$data['City'])->
            addFIeldToFilter('state',$data['State'])->
            addFIeldToFilter('postalcode',$data['PostalCode'])->
                getData();

            if($matches['0']['status']==="1"){
                return 'true';
            }elseif($matches['0']['status']==="0"){
                return 'false';
            }else{
                $soapClient = new SoapClient($wsdlUrl, array("trace" => 1));
                $result = $soapClient->GetBestMatches($data);
                if ($result->GetBestMatchesResult->Error->DescCode == 1) {
                    $response = $result->GetBestMatchesResult->Error->Desc;
                }
                if ($result->GetBestMatchesResult->Error) {
                    $response = 'false';
                    $status=false;
                } else {
                    $response = 'true';
                    $status=true;
                }

                $this->setCollection()->
                setAddress($data['Address'])->
                setCity($data['City'])->
                setState($data['State'])->
                setPostalcode($data['PostalCode'])->
                    setStatus($status)->
                    save();

                //добавляем запись в БД


                return $response;
            }

        }
    }
}