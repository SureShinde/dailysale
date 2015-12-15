<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipPayout_Model_Method_Paypal implements Unirgy_DropshipPayout_Model_Method_Interface
{
    protected $_hasExtraInfo=false;
    public function hasExtraInfo($payout)
    {
        return $this->_hasExtraInfo;
    }
    protected $_isOnline=true;
    public function isOnline()
    {
        return $this->_isOnline;
    }
    public function isTestMode()
    {
        return Mage::getStoreConfig('udropship/payout_paypal/test_mode');
    }

    protected function _getSoapWsdl()
    {
        //return $this->_getSoapWsdlFile();
        return $this->_getSoapWsdlUrl();
    }
    protected function _getSoapWsdlFile()
    {
        return Mage::getConfig()->getModuleDir('etc', 'Unirgy_DropshipPayout').DS.'paypal'.DS.'PayPalSvc.wsdl';
    }
    protected function _getSoapWsdlUrl()
    {
        return $this->isTestMode()
            ? 'https://www.sandbox.paypal.com/wsdl/PayPalSvc.wsdl'
            : 'https://www.paypal.com/wsdl/PayPalSvc.wsdl';
    }
    protected function _getSoapLocation()
    {
        return $this->isTestMode()
            ? 'https://api-3t.sandbox.paypal.com/2.0/'
            : 'https://api-3t.paypal.com/2.0/';
    }

    public function getSoapClient()
    {
        if ($this->isTestMode()) {
            $wsdlOptions = array(
                'trace' => !!$this->isTestMode(),
            );
        } else {
            $wsdlOptions = array(
                'cache_wsdl' => WSDL_CACHE_BOTH,
                'trace'      => true,
            );
        }
        $wsdlOptions['location'] = $this->_getSoapLocation();

        $client = new SoapClient($this->_getSoapWsdl(), $wsdlOptions);

        return $client;
    }

    protected function _generateUniqueId()
    {
        $unq = sha1(uniqid(microtime(), true));
        return substr($unq, 0, 15).substr($unq, 25);
    }
    
    public function pay($payout)
    {
        if ($payout instanceof Unirgy_Dropship_Model_Vendor_Statement_Interface) {
            $payout = array($payout);
        }

        $strHlp = Mage::helper('core/string');
        $ptReq = array();
        $ptToPay = array();
        foreach ($payout as $pt) {
            $pt->unsPayoutMethodError();
            $vId = $pt->getVendorId();
            if ($pt->getTotalDue()<=0) {
                $pt->setPayoutMethodErrors(array('Total Due must be greater than 0'));
            } else {
                if (!isset($ptReq[$vId])) {
                    $ptReq[$vId] = array(
                        'ReceiverEmail' => trim($pt->getVendor()->getPayoutPaypalEmail()),
                        'Amount' => array(
                            'currencyID' => Mage::app()->getStore()->getBaseCurrencyCode(),
                            '_' => 0
                         ),
                         'UniqueId' => $this->_generateUniqueId(),
                         'Note' => '',
                    );
                }
                $ptReq[$vId]['Amount']['_'] += $pt->getTotalDue();
                $ptReq[$vId]['Note'] .= ' '.$pt->getNotes();
                $ptToPay[] = $pt->getId();
            }
        }

        if (empty($ptReq)) return;
        if (count($ptReq)>=250) Mage::throwException('You can pay up to 250 recipients at once');

        foreach ($ptReq as &$ptr) {
            $ptr['Amount']['_'] = sprintf('%.2F', round($ptr['Amount']['_'], 2));
            $ptr['Note'] = $strHlp->truncate($strHlp->cleanString(preg_replace('[^\w\d ]', '', $ptr['Note'])), 3999);
        }
        unset($ptr);

        $soap = $this->getSoapClient();
        $soap->__setSoapHeaders(new SoapHeader('urn:ebay:api:PayPalAPI', 'RequesterCredentials', array(
            'Credentials' => array(
                'Username' => Mage::getStoreConfig('udropship/payout_paypal/username'),
                'Password' => Mage::getStoreConfig('udropship/payout_paypal/password'),
                'Signature' => Mage::getStoreConfig('udropship/payout_paypal/signature'),
                //'Subject' => Mage::getStoreConfig('udropship/payout_paypal/subject'),
            )
        )));

        $response = $soap->MassPay(array(
            'MassPayRequest' => array(
                'Version' => '65.1',
                'ReceiverType' => 'EmailAddress',
                'MassPayItem' => array_values($ptReq),
            )
        ));

        /*
        Mage::helper('udropship')->dump('REQUEST HEADERS', 'udpayout_paypal');
        Mage::helper('udropship')->dump($soap->__getLastRequestHeaders(), 'udpayout_paypal');
        Mage::helper('udropship')->dump('REQUEST', 'udpayout_paypal');
        Mage::helper('udropship')->dump($soap->__getLastRequest(), 'udpayout_paypal');
        Mage::helper('udropship')->dump('RESPONSE HEADERS', 'udpayout_paypal');
        Mage::helper('udropship')->dump($soap->__getLastResponseHeaders(), 'udpayout_paypal');
        Mage::helper('udropship')->dump('RESPONSE', 'udpayout_paypal');
        Mage::helper('udropship')->dump($soap->__getLastResponse(), 'udpayout_paypal');
        */

        switch($response->Ack) {
            case 'Success':
            case 'SuccessWithWarning':
                foreach ($payout as $pt) {
                    if (in_array($pt->getId(), $ptToPay)) {
                        $pt->setPaypalCorrelationId($response->CorrelationID);
                        $pt->setPaypalUniqueId($ptReq[$pt->getVendorId()]['UniqueId']);
                        if (!Mage::getStoreConfigFlag('udropship/payout_paypal/use_ipn')) {
                            $pt->afterPay();
                        } else {
                            $pt->addMessage(
                                Mage::helper('udropship')->__('Successfully send payment. Waiting for IPN to complete.'),
                                Unirgy_DropshipPayout_Model_Payout::STATUS_PAYPAL_IPN
                            )
                            ->setIsJustPaid(true);
                        }
                    }
                }
                break;
            default:
                $errArr = array();
                if (is_array($response->Errors)) {
                    foreach ($response->Errors as $_err) {
                        $errArr[] = sprintf('Error %s: %s (%s)',
                            $response->Errors->ErrorCode,
                            $response->Errors->ShortMessage,
                            $response->Errors->LongMessage
                        );
                    }
                } else {
                    $errArr[] = sprintf('Error %s: %s (%s)',
                        $response->Errors->ErrorCode,
                        $response->Errors->ShortMessage,
                        $response->Errors->LongMessage
                    );
                }
                foreach ($payout as $pt) {
                    if (in_array($pt->getId(), $ptToPay)) {
                        $pt->setPayoutMethodErrors($errArr);
                    }
                }
                Mage::throwException(implode("\n", $errArr));
        }

    }

    const DEBUG_LOG = 'payout-paypal-ipn.log';
    
    protected function _getPaypalUrl()
    {
        return $this->isTestMode()
            ? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
            : 'https://www.paypal.com/cgi-bin/webscr';
    }
    protected function _postBack()
    {
        Mage::log(Mage::app()->getRequest()->getRawBody(), null, self::DEBUG_LOG);
        $httpAdapter = new Varien_Http_Adapter_Curl();
        $httpAdapter->write(Zend_Http_Client::POST, $this->_getPaypalUrl(), '1.1', array(), 'cmd=_notify-validate&'.Mage::app()->getRequest()->getRawBody());
        $response = $httpAdapter->read();
        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);
        Mage::log('RESPONSE: '.substr($response, 0, 100), null, self::DEBUG_LOG);
        return $response;
    }
    public function processIpnPost()
    {
        $data = Mage::app()->getRequest()->getPost();
        if ($data['txn_type']!='masspay') return;
        try {
            $response = $this->_postBack();
            if ($response != 'VERIFIED') {
                throw new Exception('Masspay PayPal IPN postback failure. See '.self::DEBUG_LOG.' for details.');
            } else {
                $i=1;
                while(isset($data['unique_id_'.$i])) {
                    if ($data['status_'.$i]!='Completed') {
                        $i++;
                        continue;
                    }
                    $ptCollection = Mage::getResourceModel('udpayout/payout_collection');
                    $ptCollection->addFieldToFilter('paypal_unique_id', $data['unique_id_'.$i]);
                    foreach ($ptCollection as $pt) {
                        $pt->setTransactionId($data['masspay_txn_id_'.$i]);
                        $pt->setTransactionFee($data['mc_fee_'.$i]);
                        if ($pt->getPayoutStatus()!=Unirgy_DropshipPayout_Model_Payout::STATUS_PAID) {
                            $pt->afterPay();
                        }
                        $pt->save();
                    }
                    $i++;
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}
