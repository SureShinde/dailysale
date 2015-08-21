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
                return $matches['0']['desccode'];
            }else{
                $soapClient = new SoapClient($wsdlUrl, array("trace" => 1));
                $result = $soapClient->GetBestMatches($data);
                if ($result->GetBestMatchesResult->Error) {
                    $response = $result->GetBestMatchesResult->Error->Desc;
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
                setRealState($result->GetBestMatchesResult->Addresses->Address->State)->
                setDesccode($result->GetBestMatchesResult->Error->Desc)->
                setRealPostalcode($zip);
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

    public function getCustomerAddress($data){
        $data = $data ['0'];
        if(is_numeric($data)){
            $customer_address = Mage::getModel('customer/address')->load($data)->getData();
            $result['0'] = $customer_address['street'];
            $result['1'] = $customer_address['country_id'];
            $result['2'] = $customer_address['city'];
            $result['3'] = $customer_address['postcode'];
            $result['4'] = $customer_address['region_id'];
            $result['5'] = $customer_address['firstname'];
            $result['6'] = $customer_address['lastname'];
            $result['7'] = $customer_address['telephone'];
        }else{
            $result = null;
        }
        return $result;
    }

    public function getAddressById($address_id,$address){
        $add_data = $this->getCustomerAddress(array('0'=>$address_id));
        $address['street']['0']  = $add_data['0'];
        $address['country_id'] = $add_data['1'];
        $address['city'] = $add_data['2'];
        $address['postcode'] = $add_data['3'];
        $address['region_id'] = $add_data['4'];
        $address['firstname'] = $add_data['5'];
        $address['lastname'] = $add_data['6'];
        $address['telephone'] = $add_data['7'];
        return $address;
    }

    public function saveCustomerAddress($address_data,$address_id){
        $address = Mage::getModel('customer/address')->load($address_id);
 //       $address->delete();
        $address->addData($address_data)
            //->setCustomerId(Mage::getSingleton('customer/session')->getCustomer()->getId())
//            ->setIsDefaultBilling('1')
//            ->setIsDefaultShipping('1')
//            ->setSaveInAddressBook('1')
            ->save();
    }
}