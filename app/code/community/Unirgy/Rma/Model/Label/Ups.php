<?php

class Unirgy_Rma_Model_Label_Ups extends Unirgy_Dropship_Model_Label_Ups
{
    public function requestRma($track)
    {
        $hlp = Mage::helper('udropship');
        $this->_track = $track;

        $this->_rma = $this->_track->getRma();
        $this->_order = $this->_rma->getOrder();
        $orderId = $this->_order->getIncrementId();

        $poId = $this->_rma->getIncrementId();

        $this->_reference = $this->_track->getReference() ? $this->_track->getReference() : $orderId;

        $this->_address = $this->_order->getShippingAddress();
        $store = $this->_order->getStore();
        $currencyCode = $this->_order->getBaseCurrencyCode();
        $v = $this->getVendor();

        $skus = array();
        foreach ($this->_rma->getAllItems() as $item) {
            $skus[] = $item->getSku();
        }

        $upsData = array();
        foreach (array(
            'ups_insurance',
            'ups_delivery_confirmation',
            'ups_verbal_confirmation',
            'ups_pickup',
            'ups_container',
            'ups_dest_type',
        ) as $upsKey) {
            $upsData[$upsKey] = $track->hasData($upsKey) ? $track->getData($upsKey) : $v->getData($upsKey);
        }
        $upsData = new Varien_Object($upsData);

        $weight = $this->_track->getWeight();
        if (!$weight) {
            $weight = 0;
            foreach ($this->_rma->getAllItems() as $item) {
                $weight += $item->getWeight()*$item->getQty();
            }
        }
        $weight = sprintf('%.1f', max($weight, .1));

        $value = $this->_track->getValue();
        if (!$value) {
            $value = 0;
            foreach ($this->_rma->getAllItems() as $item) {
                $value += ($item->getBasePrice() ? $item->getBasePrice() : $item->getPrice())*$item->getQty();
            }
        }

        $length = $this->_track->getLength() ? $this->_track->getLength() : $v->getDefaultPkgLength();
        $width = $this->_track->getWidth() ? $this->_track->getWidth() : $v->getDefaultPkgWidth();
        $height = $this->_track->getHeight() ? $this->_track->getHeight() : $v->getDefaultPkgHeight();

        $a = $this->_address;

        $packageType = '02';

        if (($shippingMethod = $this->_rma->getUdropshipMethod())) {
            $arr = explode('_', $shippingMethod);
            $methodCode = $arr[1];
        } else {
            $ship = explode('_', $this->_order->getShippingMethod(), 2);
            $methodCode = $v->getShippingMethodCode($ship[1]);
        }

        $serviceCode = $this->getCode('method_code', $methodCode);
        if (empty($serviceCode))  {
            $serviceCode = $methodCode;
        }
        // if UPS CGI is used
        if (!empty($this->_methodCode[$serviceCode])) {
            $serviceCode = $this->_methodCode[$serviceCode];
        }
        $services = $hlp->getCarrierMethods('ups');
        if (empty($services[$serviceCode])) {
            Mage::throwException('Invalid shipping method');
        }

        $fromState = $a->getRegionCode();
        $fromCountry = $a->getCountryId();
        $toCountry = $v->getCountryId();
        $toState = $v->getRegionCode();

        $weightUnit = $store->getConfig('carriers/ups/unit_of_measure');
        $shipperNumber = substr($v->getUpsShipperNumber() ? $v->getUpsShipperNumber() : $store->getConfig('carriers/ups/shipper_number'), 0, 6);

        $request = new Varien_Simplexml_Element('<ShipmentConfirmRequest/>');
        $request->setNode('Request/TransactionReference/CustomerContext', $orderId);
        $request->setNode('Request/TransactionReference/XpciVersion', '1.0001');
        $request->setNode('Request/RequestAction', 'ShipConfirm');
        $request->setNode('Request/RequestOption', 'nonvalidate');
        $request->setNode('Shipment/Description', $this->_reference);

        $request->setNode('Shipment/Shipper/Name', substr($a->getName(), 0, 35));
        $request->setNode('Shipment/Shipper/AttentionName', substr($a->getName(), 0, 35));
        $request->setNode('Shipment/Shipper/ShipperNumber', $shipperNumber);
        $request->setNode('Shipment/Shipper/PhoneNumber', substr($a->getTelephone(), 0, 15));
        $request->setNode('Shipment/Shipper/EmailAddress', substr($a->getEmail(), 0, 50));
        $request->setNode('Shipment/Shipper/Address/AddressLine1', substr(trim($a->getStreet(1)), 0, 35));
        $request->setNode('Shipment/Shipper/Address/AddressLine2', substr(trim($a->getStreet(2)), 0, 35));
        $request->setNode('Shipment/Shipper/Address/AddressLine3', substr(trim($a->getStreet(3)), 0, 35));
        $request->setNode('Shipment/Shipper/Address/City', substr($a->getCity(), 0, 30));
        $request->setNode('Shipment/Shipper/Address/StateProvinceCode', $fromState);
        $request->setNode('Shipment/Shipper/Address/PostalCode', substr($a->getPostcode(), 0, 10));
        $request->setNode('Shipment/Shipper/Address/CountryCode', $fromCountry);

        $request->setNode('Shipment/ShipTo/CompanyName', substr($v->getVendorName(), 0, 35));
        $request->setNode('Shipment/ShipTo/AttentionName', substr($v->getVendorAttn(), 0, 35));
        $request->setNode('Shipment/ShipTo/PhoneNumber', substr($v->getTelephone(), 0, 15));
        $request->setNode('Shipment/ShipTo/Address/AddressLine1', substr(trim($v->getStreet(1)), 0, 35));
        $request->setNode('Shipment/ShipTo/Address/AddressLine2', substr(trim($v->getStreet(2)), 0, 35));
        $request->setNode('Shipment/ShipTo/Address/AddressLine3', substr(trim($v->getStreet(3)), 0, 35));
        $request->setNode('Shipment/ShipTo/Address/City', substr($v->getCity(), 0, 30));
        $request->setNode('Shipment/ShipTo/Address/StateProvinceCode', $toState);
        $request->setNode('Shipment/ShipTo/Address/PostalCode', substr($v->getZip(), 0, 10));
        $request->setNode('Shipment/ShipTo/Address/CountryCode', $toCountry);
        if ($store->getConfig('carriers/ups/dest_type')=='RES') {
            $request->setNode('Shipment/ShipTo/Address/ResidentialAddress', '');
        }
        if (Mage::getStoreConfigFlag('carriers/ups/negotiated_rates', $store)) {
            $request->setNode('Shipment/NegotiatedRatesIndicator', '');
        }

        $request->setNode('Shipment/Service/Code', $serviceCode);
        $request->setNode('Shipment/Service/Description', $services[$serviceCode]);

        if ($packageType!='01' && ($fromCountry=='US') && (($toCountry=='CA') || ($toCountry=='US' && $toState=='PR')))  {
            $request->setNode('Shipment/InvoiceLineTotal/CurrencyCode', $currencyCode);
            $request->setNode('Shipment/InvoiceLineTotal/MonetaryValue', round($value));
        }
        if (($thirdPartyNumber = $v->getUpsThirdpartyAccountNumber())) {
            $request->setNode('Shipment/PaymentInformation/BillThirdParty/BillThirdPartyShipper/AccountNumber', $thirdPartyNumber);
            $request->setNode('Shipment/PaymentInformation/BillThirdParty/BillThirdPartyShipper/ThirdParty/Address/PostalCode', $v->getUpsThirdpartyPostcode());
            $request->setNode('Shipment/PaymentInformation/BillThirdParty/BillThirdPartyShipper/ThirdParty/Address/CountryCode', $v->getUpsThirdpartyCountry());
        } else {
            $request->setNode('Shipment/PaymentInformation/Prepaid/BillShipper/AccountNumber', $shipperNumber);
        }

        $request->setNode('Shipment/Package/Description', $this->_reference);
        $request->setNode('Shipment/Package/PackagingType/Code', $packageType);
        $request->setNode('Shipment/Package/PackagingType/Description', $this->_packageType[$packageType]);
        $request->setNode('Shipment/Package/Dimensions/UnitOfMeasure/Code', $v->getDimensionUnits());
        $request->setNode('Shipment/Package/Dimensions/Length', $length);
        $request->setNode('Shipment/Package/Dimensions/Width', $width);
        $request->setNode('Shipment/Package/Dimensions/Height', $height);
        $request->setNode('Shipment/Package/PackageWeight/UnitOfMeasurement/Code', $weightUnit);
        $request->setNode('Shipment/Package/PackageWeight/Weight', $weight);
        if ($fromCountry=='US' && $toCountry=='US') {
            $request->setNode('Shipment/Package/ReferenceNumber/Code', 'TN');
            $request->setNode('Shipment/Package/ReferenceNumber/Value', substr($this->_reference, 0, 35));
        }
        if ($upsData->getUpsDeliveryConfirmation()) {
            $request->setNode('Shipment/Package/PackageServiceOptions/DeliveryConfirmation/DCISType', $upsData->getUpsDeliveryConfirmation());
            $request->setNode('Shipment/Package/PackageServiceOptions/DeliveryConfirmation/DCISNumber', '');
        }
        if ($upsData->getUpsInsurance()) {
            //$request->setNode('Shipment/Package/PackageServiceOptions/InsuredValue/Type/Code', '01'); // 01-EVS, 02-DVS
            $request->setNode('Shipment/Package/PackageServiceOptions/InsuredValue/CurrencyCode', $currencyCode);
            $request->setNode('Shipment/Package/PackageServiceOptions/InsuredValue/MonetaryValue', round($value));
        }
        if ($upsData->getUpsVerbalConfirmation()) {
            $request->setNode('Shipment/Package/PackageServiceOptions/VerbalConfirmation/ContactInfo/Name', substr($store->getConfig('carriers/ups/shipper_attention'), 0, 35));
            $request->setNode('Shipment/Package/PackageServiceOptions/VerbalConfirmation/ContactInfo/PhoneNumber', substr($store->getConfig('carriers/ups/shipper_phone'), 0, 15));
        }
        if ($v->getUpsReleaseWithoutSignature()) {
            $request->setNode('Shipment/Package/PackageServiceOptions/ShipperReleaseIndicator', '');
        }

        switch ($v->getLabelType()) {
        case 'PDF':
            $request->setNode('LabelSpecification/LabelPrintMethod/Code', 'GIF');
            $request->setNode('LabelSpecification/LabelImageFormat/Code', 'GIF');
            $request->setNode('LabelSpecification/HTTPUserAgent', 'Mozilla/4.5');
            break;

        case 'EPL':
            $request->setNode('LabelSpecification/LabelPrintMethod/Code', 'EPL');
            $request->setNode('LabelSpecification/LabelStockSize/Height', '4');
            $request->setNode('LabelSpecification/LabelStockSize/Width', '8');
            break;

        default:
            Mage::throwException('Invalid vendor label type');
        }

        if ($fromCountry!=$toCountry) {
            $request->setNode('Shipment/SoldTo/CompanyName', substr($a->getName(), 0, 35));
            $request->setNode('Shipment/SoldTo/AttentionName', substr($v->getVendorAttn(), 0, 35));
            $request->setNode('Shipment/SoldTo/PhoneNumber', substr($v->getTelephone(), 0, 15));
            $request->setNode('Shipment/SoldTo/Address/AddressLine1', substr(trim($v->getStreet(1)), 0, 35));
            $request->setNode('Shipment/SoldTo/Address/AddressLine2', substr(trim($v->getStreet(2)), 0, 35));
            $request->setNode('Shipment/SoldTo/Address/AddressLine3', substr(trim($v->getStreet(3)), 0, 35));
            $request->setNode('Shipment/SoldTo/Address/City', substr($v->getCity(), 0, 30));
            $request->setNode('Shipment/SoldTo/Address/StateProvinceCode', $toState);
            $request->setNode('Shipment/SoldTo/Address/PostalCode', substr($v->getZip(), 0, 10));
            $request->setNode('Shipment/SoldTo/Address/CountryCode', $toCountry);
            if ($store->getConfig('carriers/ups/dest_type')=='RES') {
                $request->setNode('Shipment/SoldTo/Address/ResidentialAddress', '');
            }

            $request->setNode('Shipment/ShipmentServiceOptions/InternationalForms/FormType', self::INT_FORM_TYPE_INVOICE);
            $request->setNode('Shipment/ShipmentServiceOptions/InternationalForms/InvoiceNumber', $orderId);
            $request->setNode('Shipment/ShipmentServiceOptions/InternationalForms/InvoiceDate', date('Ymd', strtotime($this->_order->getCreatedAt())));
            $request->setNode('Shipment/ShipmentServiceOptions/InternationalForms/PurchaseOrderNumber', '');
            $request->setNode('Shipment/ShipmentServiceOptions/InternationalForms/TermsOfShipment', 'DDP');
            $request->setNode('Shipment/ShipmentServiceOptions/InternationalForms/ReasonForExport', 'SALE');
            $request->setNode('Shipment/ShipmentServiceOptions/InternationalForms/CurrencyCode', $currencyCode);
            $request->setNode('Shipment/ShipmentServiceOptions/InternationalForms/FreightCharges/MonetaryValue', sprintf('%.2f', $this->_order->getShippingAmount()));
            $root = $request->Shipment->ShipmentServiceOptions->InternationalForms;

            $number = 0;
            foreach ($this->_rma->getAllItems() as $item) {
                $oItem = Mage::getModel('sales/order_item')->load($item->getOrderItemId());
                $requestProd = $root->addChild('Product');
                $description = $oItem->getName() . ($oItem->getDescription() != '' ? ' - '.$oItem->getDescription() : '').', Qty: '.(1*$item->getQty());
                $requestProd->setNode('Description', substr($description, 0, 35));
                $requestProd->setNode('OriginCountryCode', $fromCountry);
                $requestProd->setNode('CommodityCode', $oItem->getCommodityCode() ? $oItem->getCommodityCode()  : '');
                $requestProd->setNode('NumberOfPackagesPerCommodity', 1);
                $requestProd->setNode('Unit/Number', round($item->getQty()));
                $requestProd->setNode('Unit/Value', sprintf('%.4f', $oItem->getPrice()));
                $requestProd->setNode('Unit/UnitOfMeasurement/Code', 'PCS');
                $requestProd->setNode('ProductWeight/UnitOfMeasurement/Code', $weightUnit);
                $requestProd->setNode('ProductWeight/Weight', sprintf('%.1f', max($item->getWeight(), .1)));
            }
        }

        $xmlRequest = '<?xml version="1.0"?>'.$request->asNiceXml();
        $this->setXMLAccessRequest();

        $xmlResponse = $this->_callShippingXml('ShipConfirm', $xmlRequest);

        $response = new Varien_Simplexml_Element($xmlResponse);
        $this->_validateResponse($response);

        $shipmentDigest = (string)$response->descend('ShipmentDigest');
        if (!$shipmentDigest) {
            Mage::throwException("Could not retrieve shipment digest vaue.");
        }

        $xmlRequest =<<<EOT
<?xml version="1.0"?>
<ShipmentAcceptRequest>
    <Request>
         <TransactionReference>
              <CustomerContext>{$orderId}</CustomerContext>
              <XpciVersion>1.0001</XpciVersion>
         </TransactionReference>
         <RequestAction>ShipAccept</RequestAction>
    </Request>
    <ShipmentDigest><![CDATA[{$shipmentDigest}]]></ShipmentDigest>
</ShipmentAcceptRequest>
EOT;
        $xmlResponse = $this->_callShippingXml('ShipAccept', $xmlRequest);
        $response = new Varien_Simplexml_Element($xmlResponse);
#Mage::log($response);
        $this->_validateResponse($response);

        $xmlPackages = $response->descend("ShipmentResults/PackageResults");
        if (!$xmlPackages) {
            Mage::throwException('Could not retrieve shipping labels.');
        }

        $extra = array(
            'batch' => $this->getBatch()->getId(),
            'ref' => $this->_reference,
            'date' => strtoupper(date('M d Y')),
            'actwt' => $weight,
            'wunit' => (string)$response->descend("ShipmentResults/BillingWeight/UnitOfMeasurement/Code"),
            'pkg' => 1, //TODO multiple pkg num
            'method' => $services[$serviceCode],
            'billwt' => (string)$response->descend("ShipmentResults/BillingWeight/Weight"),
            'trkid' => (string)$response->descend("ShipmentResults/ShipmentIdentificationNumber"),
            'cur' => (string)$response->descend("ShipmentResults/ShipmentCharges/TransportationCharges/CurrencyCode"),
            'orderid' => $orderId,
            'value' => $value,
            'hndlfee' => $v->getHandlingFee(),
            'svc' => (string)$response->descend("ShipmentResults/ShipmentCharges/TransportationCharges/MonetaryValue"),
            'svcopt' => (string)$response->descend("ShipmentResults/ShipmentCharges/ServiceOptionsCharges/MonetaryValue"),
            'svcpub' => (string)$response->descend("ShipmentResults/ShipmentCharges/TotalCharges/MonetaryValue"),
            'svcneg' => (string)$response->descend("ShipmentResults/ShipmentCharges/NegotitatedRates/NetSummaryCharges/GrandTotal/MonetaryValue"),
             // wordwrap items into 48 (55-7) chars max, and show only 6 first lines
            'items' => join("\n", array_slice(explode("\n", wordwrap(join(', ', $skus), 48, "\n", true)), 0, 6)),
        );
        $extra['svctot'] = $extra['svcpub']+$extra['hndlfee'];

        $labelImages = array();

        foreach ($xmlPackages as $package) {
            $tracking = (string)$package->TrackingNumber;
            $labelImageFormat = (string)$package->descend('LabelImage/LabelImageFormat/Code');
            $labelImage = (string)$package->descend('LabelImage/GraphicImage');
            if ($labelImage) {
                $labelImages[] = $this->processImage($v->getLabelType(), $labelImage, $extra);
            }

            $intLabelImage = (string)$package->descend('LabelImage/InternationalSignatureGraphicImage');
            if ($intLabelImage) {
                $labelImages[] = $this->processImage($v->getLabelType(), $intLabelImage);
            }
            break;
        }


        $track->setCarrierCode('ups');
        $track->setTitle($store->getConfig('carriers/ups/title'));
        $track->setNumber($tracking);
        $track->setFinalPrice($extra['svctot']);
        $track->setResultExtra(serialize($extra));

        $labelModel = Mage::helper('udropship')->getLabelTypeInstance($v->getLabelType());
        $labelModel->setVendor($v)->updateTrack($track, $labelImages);
        return $this;
    }
}