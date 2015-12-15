<?php
require_once Mage::getBaseDir('lib').DS.'SweetTooth/pest/vendor/autoload.php';

class Fiuze_DropshipPo_Block_Vendor_Po_ImportOrderAftershipStatus extends Mage_Core_Block_Template
{
    public $messageMail;
    private $_trackingNumbersContent;

    protected function _prepareLayout()
    {
        Mage::getConfig()->init()->loadEventObservers('crontab');
        Mage::app()->addEventArea('crontab');
        Mage::dispatchEvent('default');

        parent::_prepareLayout();
        $this->setResultStatus($this->checkStatusTrackNumber());
        return $this;
    }

    public function checkStatusTrackNumber(){
        $resultStutus = array();
        $this->_trackingNumbersContent = Mage::getSingleton('core/session')->getTrackingNumbersContent();

        if(!is_null($this->_trackingNumbersContent)){
            $messageMail = array();
            foreach($this->_trackingNumbersContent as $itemRow){
                Mage::getSingleton('core/session')->unsTrackingNumbersContent();
                $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');

                list($orderId,$trackingNumber) = preg_split("/;/", $itemRow);
                $messageMail[$trackingNumber] = array();
                $tracks = Mage::getModel('track/track')
                    ->getCollection()
                    ->addFieldToFilter('tracking_number', array('eq' => $trackingNumber))
                    ->addFieldToFilter('order_id', array('eq' => $orderId))
                    ->getItems();
                $track = reset($tracks);
                if($track){
                    $trackingId = $track->getTrackingId();
                    $message = $track->getErrorTracking();
                    if($trackingId){
                        $trackings = new AfterShip\Trackings($api_key);
                        $responseJson = $trackings->get_by_id($trackingId);
                        if(!$message){
                            $message = 'sent successfully';
                        }
                        $resultStutus[$itemRow] = $message.'&#13;&#10;'.'Status --->'.$this->_getStatus($responseJson);
                        $messageMail[$trackingNumber]['status'] = $this->_getStatus($responseJson);
                        $messageMail[$trackingNumber]['message'] = $message;
                        $messageMail[$trackingNumber]['orderId'] = $orderId;
                    }else{
                        $resultStutus[$itemRow] = $message.'&#13;&#10;';
                        $messageMail[$trackingNumber]['message'] = $message;
                        $messageMail[$trackingNumber]['orderId'] = $orderId;
                    }
                }
            }
            $this->messageMail = $messageMail;
            return $resultStutus;
        }
    }

    protected function _getStatus($responseJson){
        $http_status = $responseJson['meta']['code'];
        $data = $responseJson['data'];
        if($http_status == '200' && array_key_exists('tracking',$data)){
            $tracking = $data['tracking'];
            return array_key_exists('tag',$tracking)?$tracking['tag']:'';
        }
        return '';
    }

    /**
     * Sending email with Invoice data
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function sendTrackingNotificationEmail($tracks)
    {
        if(!is_null($this->_trackingNumbersContent)) {
            return 'All tracking IDs have been uploaded. We will e-mail you the results within 24 hours';
            $emailTemplate = Mage::getModel('core/email_template')
                ->loadDefault('aftership_tracking_email');
            $emailTemplateVariables = array();
            $emailTemplateVariables['object'] = $tracks;

            $processedTemplate = $emailTemplate->getProcessedTemplate($emailTemplateVariables);
            $vendor = Mage::getSingleton('udropship/session')->getVendor();
            $emailTemplate->setSenderName($vendor->getVendorName());
            $emailTemplate->setSenderEmail($vendor->getEmail());
            $emailTemplate->setTemplateSubject($emailTemplateVariables);
            $emailTemplate->send($vendor->getEmail(), $vendor->getVendorName(), $emailTemplateVariables);
        }
    }
}
