<?php

class Fiuze_AddressValidation_Model_Addresses extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('fiuze_addressvalidation/addresses');
    }

    public function checkAddress($param){
        if($param['1']=='US') {

            //if custom WSDL is null - use trial WSDL
            if(Mage::getStoreConfig('fiuze_address_validation/license_group/wsdl_input', Mage::app()->getStore())==""){
                $wsdlUrl = 'https://trial.serviceobjects.com/AV3/api.svc?wsdl';
            }else {
                $wsdlUrl = Mage::getStoreConfig('fiuze_address_validation/license_group/wsdl_input', Mage::app()->getStore());
            }
            $data['Business'] = Mage::getStoreConfig('fiuze_address_validation/license_group/name_input', Mage::app()->getStore());
            $data['Address'] = $param['0'];
            $data['Address2'] = '';
            $data['City'] = $param['2'];
            $data['State'] = Mage::getModel('directory/region')->load($param['4'])->getCode();
            $data['PostalCode'] = $param['3'];
            $data['LicenseKey'] = Mage::getStoreConfig('fiuze_address_validation/license_group/license_input', Mage::app()->getStore());
            //find matches
            $matches = $this->getCollection()->
            addFIeldToFilter('address',$data['Address'])->
            addFIeldToFilter('city',$data['City'])->
            addFIeldToFilter('state',$data['State'])->
            addFIeldToFilter('postalcode',$data['PostalCode'])->
                getData();
            //create response
            if($matches['0']['status']==="1"){
                return 'true';
            }elseif($matches['0']['status']==="0"){
                return 'false';
            }else{
                $soapClient = new SoapClient($wsdlUrl, array("trace" => 1));
                $result = $soapClient->GetBestMatches($data);
                if ($result->GetBestMatchesResult->Error->DescCode == 1) {
                    return $result->GetBestMatchesResult->Error->Desc;
                }
                if ($result->GetBestMatchesResult->Error) {
                    $response = 'false';
                    $status=false;
                } else {
                    $response = 'true';
                    $status=true;
                }
                $zip = (string)$result->GetBestMatchesResult->Addresses->Address->Zip;

                $this->
                    //set input data
                setAddress($data['Address'])->
                setCity($data['City'])->
                setState($data['State'])->
                setPostalcode($data['PostalCode'])->
                    //set status
                setStatus($status)->
                    //set address from result
                setRealAddress($result->GetBestMatchesResult->Addresses->Address->Address1)->
                setRealCity($result->GetBestMatchesResult->Addresses->Address->City)->
                setRealState($result->GetBestMatchesResult->Addresses->Address->State);
                $this->setRealPostalcode($zip);
                $this->save();

                return $response;
            }

        }
    }

    public function getRealAddress($data){

        $real_data = $this->getCollection()->
        addFIeldToFilter('address',$data['street']['0'])->
        addFIeldToFilter('city',$data['city'])->
        addFIeldToFilter('state',Mage::getModel('directory/region')->load($data['region_id'])->getCode())->
        addFIeldToFilter('postalcode',$data['postcode'])->
        getData();

        $data['street']['0']=$real_data['0']['real_address'];
        $data['city']=$real_data['0']['real_city'];
        $data['postcode']=$real_data['0']['real_postalcode'];

        return $data;
    }
}