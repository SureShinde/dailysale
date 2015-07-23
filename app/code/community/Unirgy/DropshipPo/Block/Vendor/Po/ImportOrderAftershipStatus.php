<?php
require_once Mage::getBaseDir('lib').DS.'SweetTooth/pest/vendor/autoload.php';

class Unirgy_DropshipPo_Block_Vendor_Po_ImportOrderAftershipStatus extends Mage_Core_Block_Template
{
    const ENDPOINT_TRACKING = 'https://api.aftership.com/v4/trackings';
    const ENDPOINT_AUTHENTICATE = 'https://api.aftership.com/v4/couriers';

    //const ENDPOINT_TRACKING0 = 'https://api.aftership.com/v4/trackings/9374869903500485498778';

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->checkStatusTrackNumber();
        return $this;
    }

    public function checkStatusTrackNumber(){
        $trackingNumbersContent = Mage::registry('tracking_numbers_content', Mage::app()->getStore()->getId());
        $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');

        $this->_callApiTracking($api_key, '9374869903500485498778');

//        $trackings = new AfterShip\Trackings($api_key);
//        $response = $trackings->get('dhl', '9374869903500485498778', array('title','tracking'));
//
//
//        $trackings = new AfterShip\Trackings($api_key);
//        $response = $trackings->get_by_id('9374869903500485498778', array('title','order_id'));
//
//        $last_check_point = new AfterShip\LastCheckPoint($api_key);
//        $response = $last_check_point->get_by_id('9374869903500485498778');
//
//        $courier = new AfterShip\Couriers($api_key);
//        $response = $courier->detect('9374869903500485498778');
//
//        $trackings = new AfterShip\Trackings($api_key);
//        $response = $trackings->delete_by_id('9374869903500485498778');
//
//        $this->_callApiAuthenticate($api_key);

        $serg = 541;
//100021959;9374869903500485498775
    }

    private function _callApiTracking($api_key, $tracking_number) {
        $url_params = array('tracking'	=> array(
            'tracking_number'	        => $tracking_number
        ));

        $json_params = json_encode($url_params);

        $headers = array(
            'aftership-api-key: ' . $api_key,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::ENDPOINT_TRACKING.'/'.$tracking_number);
        curl_setopt($ch, CURLOPT_POST, false);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $json_params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        //handle SSL certificate problem: unable to get local issuer certificate issue
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //the SSL is not correct
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //the SSL is not correct
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response;
    }

    private function _callApiCreateTracking($api_key, $tracking_number, $carrier_code, $country_id, $telephone, $email, $title, $order_id, $customer_name) {
        $url_params = array('tracking'	=> array(
            'tracking_number'	        => $tracking_number,
            'destination_country_iso3'  => $country_id,
            'smses'				        => $telephone,
            'emails'			        => $email,
            'title'				        => $title,
            'order_id'			        => $order_id,
            'customer_name'		        => $customer_name,
            'source'			        => 'magento'
        ));

        $json_params = json_encode($url_params);

        $headers = array(
            'aftership-api-key: ' . $api_key,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_params)
        );
       // "/trackings/:slug/:tracking_number"
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::ENDPOINT_TRACKING);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        //handle SSL certificate problem: unable to get local issuer certificate issue
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //the SSL is not correct
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //the SSL is not correct
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response;
    }

    /**
     * Call API to authenticate
     * @param $api_key
     * @return HTTP status code
     */
    private function _callApiAuthenticate($api_key) {
        $headers = array(
            'aftership-api-key: ' . $api_key,
            'Content-Type: application/json',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::ENDPOINT_AUTHENTICATE);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        //handle SSL certificate problem: unable to get local issuer certificate issue
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //the SSL is not correct
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //the SSL is not correct
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_status;
    }
}