<?php

class Unirgy_Rma_Model_Label_Endicia extends Unirgy_Dropship_Model_Label_Endicia
{
    public function requestRma($track)
    {
        $hlp = Mage::helper('udropship');

        $v = $this->getVendor();

        $rma = $track->getRma();
        $order = $rma->getOrder();
        if (!$order->getShippingMethod()) {
            return $this;
        }
        $this->setStore($order->getStore());

        $address = $order->getShippingAddress();
        $customer = $hlp->getOrderCustomer($order);

        $reference = $track->getReference() ? $track->getReference() : $order->getIncrementId();

        $endiciaData = array();
        foreach (array(
             'endicia_stealth',
             'endicia_label_type',
             'endicia_mail_class',
             'endicia_mailpiece_shape',
             'endicia_delivery_confirmation',
             'endicia_signature_confirmation',
             'endicia_return_receipt',
             'endicia_electronic_return_receipt',
             'endicia_insured_mail',
             'endicia_restricted_delivery',
             'endicia_cod',
        ) as $endiciaKey) {
            $endiciaData[$endiciaKey] = $track->hasData($endiciaKey) ? $track->getData($endiciaKey) : $v->getData($endiciaKey);
        }
        $endiciaData = new Varien_Object($endiciaData);

        if (($shippingMethod = $rma->getUdropshipMethod())) {
            $arr = explode('_', $shippingMethod);
            $methodCode = $arr[1];
        } else {
            $ship = explode('_', $order->getShippingMethod(), 2);
            $methodCode = $v->getShippingMethodCode($ship[1]);
        }

        if ($track->getUseMethodCode()) {
            $methodCode = $track->getUseMethodCode();
        }

        $mappedMC = $this->mapMethodToMailclass($methodCode);
        //$usps = Mage::getSingleton('shipping/config')->getCarrierInstance('usps');
        $mailClass = $track->getEndiciaMailClass()
            ? $track->getEndiciaMailClass()
            : ($mappedMC ? $mappedMC : $endiciaData->getEndiciaMailClass());

        $skus = array();
        foreach ($rma->getAllItems() as $item) {
            $skus[] = $item->getSku();
        }

        $weight = $track->getWeight();
        if (!$weight) {
            $weight = 0;
            foreach ($rma->getAllItems() as $item) {
                $weight += $item->getWeight()*$item->getQty();
            }
        }
        $weight = max($weight, 1/16);

        $value = $track->getValue();
        if (!$value) {
            $value = 0;
            foreach ($rma->getAllItems() as $item) {
                $value += ($item->getBasePrice() ? $item->getBasePrice() : $item->getPrice())*$item->getQty();
            }
        }

        $length = $track->getLength() ? $track->getLength() : $v->getDefaultPkgLength();
        $width = $track->getWidth() ? $track->getWidth() : $v->getDefaultPkgWidth();
        $height = $track->getHeight() ? $track->getHeight() : $v->getDefaultPkgHeight();

        $labelRotate = $v->getPdfLabelRotate() ? 'Rotate'.$v->getPdfLabelRotate() : 'None';
        $labelType = $endiciaData->getEndiciaLabelType();

        if ($v->getCountryId()!=$address->getCountryId()) {
            $labelType = 'International';
            $labelRotate = 'Rotate270';
        }
        if (preg_match('#^([0-9]{5})-([0-9]{4})$#', $v->getZip(), $m)) {
            $toPostalCode = $m[1];
            $toZip4 = $m[2];
        } else {
            $toPostalCode = $v->getZip();
            $toZip4 = '';
        }
        if (preg_match('#^([0-9]{5})-([0-9]{4})$#', $address->getPostcode(), $m)) {
            $fromPostalCode = $m[1];
            $fromZip4 = $m[2];
        } else {
            $fromPostalCode = $address->getPostcode();
            $fromZip4 = '';
        }

        $data = array(
            'RequesterID' => $v->getEndiciaRequesterId(),
            'AccountID' => $v->getEndiciaAccountId(),
            'PassPhrase' => $v->getEndiciaPassPhrase(),
            'MailClass' => $mailClass,
            'DateAdvance' => 0,
            'WeightOz' => ceil($weight*16),
            'CostCenter' => 0,
            'Value' => $value,
            'InsuredValue' => $value,
            'MailpieceShape' => $endiciaData->getEndiciaMailpieceShape(),
            'MailpieceDimensions' => array(
                'Length' => $length,
                'Width' => $width,
                'Height' => $height,
            ),
            'Services' => array(
                'DeliveryConfirmation' => $endiciaData->getEndiciaDeliveryConfirmation() && $labelType != 'International'
                        ? 'ON' : 'OFF',
                'SignatureConfirmation' => $endiciaData->getEndiciaSignatureConfirmation() ? 'ON' : 'OFF',
                'ReturnReceipt' => $endiciaData->getEndiciaReturnReceipt() ? 'ON' : 'OFF',
                'ElectronicReturnReceipt' => $endiciaData->getEndiciaElectronicReturnReceipt() ? 'ON' : 'OFF',
                'COD' => $endiciaData->getEndiciaCod() ? 'ON' : 'OFF',
                'RestrictedDelivery' => $endiciaData->getEndiciaRestrictedDelivery() ? 'ON' : 'OFF',
                'InsuredMail' => $endiciaData->getEndiciaInsuredMail(),
            ),
            'Description' => $reference,
            'PartnerCustomerID' => $customer->getIncrementId() ? $customer->getIncrementId() : 'Guest',
            'PartnerTransactionID' => $order->getIncrementId(),
            'ToName' =>  $v->getVendorAttn(),
            'ToCompany' => $v->getVendorName(),
            'ToAddress1' => $v->getStreet(1),
            'ToAddress2' => $v->getStreet(2),
            'ToAddress3' => $v->getStreet(3),
            'ToAddress4' => $v->getStreet(4),
            'ToCity' => $v->getCity(),
            'ToState' => $v->getRegionCode(),
            'ToPostalCode' => $toPostalCode,
            'ToZIP4' => $toZip4,
            'ToCountry' => $hlp->getCountryName($v->getCountryId()),
            'ToPhone' => $v->getTelephone() ? preg_replace('#[^0-9]#', '', $v->getTelephone()) : '8005551212',
            'FromName' => $address->getName(),
            'ReturnAddress1' => $address->getStreet(1),
            'ReturnAddress2' => $address->getStreet(2),
            'ReturnAddress3' => $address->getStreet(3),
            'ReturnAddress4' => $address->getStreet(4),
            'FromCity' => $address->getCity(),
            'FromState' => $address->getRegionCode(),
            'FromPostalCode' => $fromPostalCode,
            'FromZIP4' => $fromZip4,
            'OriginCountry' => $hlp->getCountryName($address->getCountryId()),
            'FromPhone' => preg_replace('#[^0-9]#', '', $address->getTelephone()),
            'Test' => $v->getEndiciaTestMode() ? 'YES' : 'NO',
            'LabelType' => $labelType,
            'ImageRotation' => $labelRotate,
            'ResponseOptions' => array(
                'PostagePrice' => 'TRUE',
            ),
            'RubberStamp1' => 'Order # '.$order->getIncrementId(),
            'RubberStamp2' => $order->getIncrementId()!=$reference ? 'Ref. '.$reference : '',

            'CustomsFormType' => $v->getEndiciaCustomsFormType(),
            'CustomsQuantity1' => 0,
            'CustomsValue1' => 0,
            'CustomsWeight1' => 0,
            'CustomsQuantity2' => 0,
            'CustomsValue2' => 0,
            'CustomsWeight2' => 0,
            'CustomsQuantity3' => 0,
            'CustomsValue3' => 0,
            'CustomsWeight3' => 0,
            'CustomsQuantity4' => 0,
            'CustomsValue4' => 0,
            'CustomsWeight4' => 0,
            'CustomsQuantity5' => 0,
            'CustomsValue5' => 0,
            'CustomsWeight5' => 0,
        );
        switch ($v->getLabelType()) {
        case 'PDF':
            $data['ImageFormat'] = 'PNG';
            $data['LabelSize'] = '4x6';
            break;

        case 'EPL':
            $data['ImageFormat'] = 'EPL2';
            $data['LabelSize'] = '4x6';
            $data['LabelRotate'] = 'Rotate180';
/*
EPL2 and ZPLII are supported for:
- Default label type for domestic mail classes.
- International label type when used with
    - Priority Mail International Flat Rate Envelope
    - Small Flat Rate Box
    - First Class Mail International
*/
            break;

        default:
            Mage::throwException('Invalid vendor label type');
        }

        $client = $this->getSoapClient($v);

        $result = $client->GetPostageLabel(array('LabelRequest'=>$data));

        Mage::helper('udropship')->dump('REQUEST', 'endicia_label');
        Mage::helper('udropship')->dump($client->__getLastRequestHeaders(), 'endicia_label');
        Mage::helper('udropship')->dump($client->__getLastRequest(), 'endicia_label');
        Mage::helper('udropship')->dump('RESPONSE', 'endicia_label');
        Mage::helper('udropship')->dump($client->__getLastResponseHeaders(), 'endicia_label');
        Mage::helper('udropship')->dump($client->__getLastResponse(), 'endicia_label');

        if (!$result || empty($result->LabelRequestResponse)) {
            Mage::throwException('Invalid API response');
        }
        $xml = $result->LabelRequestResponse;

        if ((int)$xml->Status != 0) {
            Mage::throwException($xml->ErrorMessage);
        }

        if (empty($xml->Base64LabelImage) && empty($xml->Label->Image)) {
            Mage::throwException('Unable to retrieve the label.');
        }

        $track->setCarrierCode('usps');
        $track->setTitle('USPS');
        $track->setNumber($xml->TrackingNumber);
        $track->setFinalPrice($xml->FinalPostage);
        $labelImages = array();

        $fees = $xml->PostagePrice->Fees;
        $extra = array(
            'batch' => $this->getBatch()->getId(),
            'ref' => $reference,
            'date' => strtoupper(date('M d Y')),
            'actwt' => $weight,
            'trkid' => $xml->TrackingNumber,
            'cur' => 'USD',
            'wunit' => 'LBS',
            'pkg' => 1, //TODO multiple pkg num
            'method' => $xml->PostagePrice->Postage->MailService,#$methodCode,
            'orderid' => $order->getIncrementId(),
            'value' => $value,
            'hndlfee' => $v->getHandlingFee(),
             // wordwrap items into 48 (55-7) chars max, and show only 6 first lines
            'items' => join("\n", array_slice(explode("\n", wordwrap(join(', ', $skus), 48, "\n", true)), 0, 6)),
            'svc' => $xml->PostagePrice->Postage->TotalAmount,
            'svcpub' => $xml->FinalPostage,
            'svcopt' => $fees->TotalAmount,
            'svccom' => $fees->CertificateOfMailing,
            'svccm' => $fees->CertifiedMail,
            'svccod' => $fees->CollectOnDelivery, //future
            'svcdc' => $fees->DeliveryConfirmation,
            'svcerr' => $fees->ElectronicReturnReceipt, //?
            'svcim' => $fees->InsuredMail,
            'svcrm' => $fees->RegisteredMail,
            'svcrd' => $fees->RestrictedDelivery, //future
            'svcrr' => $fees->ReturnReceipt,
            'svcrrm' => $fees->ReturnReceiptForMerchandise, //future
            'svcsc' => $fees->SignatureConfirmation,
            'svcsh' => $fees->SpecialHandling, //future
        );
        $extra['svctot'] = $extra['svcpub']+$extra['hndlfee'];

        if (!empty($xml->Base64LabelImage)) {
            $labelImages[] = $this->processImage($v->getLabelType(), $xml->Base64LabelImage, $extra);
        } elseif (!empty($xml->Label->Image->_)) {
            $labelImages[] = $this->processImage($v->getLabelType(), $xml->Label->Image->_, $extra);
        } else {
            foreach ($xml->Label->Image as $image) {
                if (!empty($image->_)) {
                    $labelImages[] = $this->processImage($v->getLabelType(), $image->_, $extra);
                } else {
                    $labelImages[] = $this->processImage($v->getLabelType(), $image, $extra);
                }
            }
        }

        $labelModel = Mage::helper('udropship')->getLabelTypeInstance($v->getLabelType());
        $labelModel->setVendor($v)->updateTrack($track, $labelImages);
        if ($v->getLabelType()=='PDF' && $labelType=='International') {
            // for customs forms - renders on the whole page
            $track->setLabelRenderOptions(serialize(array(
                'r' => 90,
                'l' => .5,
                't' => .5,
                'w' => 10,
                'h' => 6.875,
            )));
        }

        $balanceThreshold = $v->getEndiciaBalanceThreshold();
        $recreditAmount = $v->getEndiciaRecreditAmount();
        if ($balanceThreshold && $recreditAmount && $xml->PostageBalance<=$balanceThreshold) {
            try {
                $this->buyPostage($recreditAmount);
            } catch (Exception $e) {
                Mage::log('Unable to recredit Endicia account: '.$e->getMessage());
            }
        }

        return $this;
    }
}