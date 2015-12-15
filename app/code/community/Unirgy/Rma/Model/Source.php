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
 * @package    Unirgy_RMA
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_Rma_Model_Source extends Unirgy_Dropship_Model_Source_Abstract
{

    public function toOptionHash($selector=false)
    {
        $hlp = Mage::helper('udropship');

        $options = array();

        switch ($this->getPath()) {

        case 'rma_status':
            $options = Mage::helper('urma')->getStatusTitles();
            break;
        case 'rma_reason':
            $options = Mage::helper('urma')->getReasonTitles();
            break;
        case 'rma_item_condition':
            $options = Mage::helper('urma')->getItemConditionTitles();
            break;

        case 'rma_use_ups_account':
        case 'rma_use_endicia_account':
        case 'rma_use_fedex_account':
            $options = array(
                'global' => Mage::helper('udropship')->__('Global'),
                'vendor' => Mage::helper('udropship')->__('Vendor'),
            );
            break;

        case 'vendor_rma_grid_sortdir':
            $options = array(
                'asc' => Mage::helper('udropship')->__('Ascending'),
                'desc' => Mage::helper('udropship')->__('Descending'),
            );
            break;

        case 'vendor_rma_grid_sortby':
            $options = array(
                'increment_id' => Mage::helper('udropship')->__('RMA ID'),
                'rma_date' => Mage::helper('udropship')->__('RMA Date'),
                'udropship_status' => Mage::helper('udropship')->__('RMA Status'),
                'rma_reason' => Mage::helper('udropship')->__('Reason to return'),
            );
            break;

        case 'rma_use_address':
            $options = array(
                'origin' => Mage::helper('udropship')->__('Origin'),
                'custom' => Mage::helper('udropship')->__('Custom'),
            );
            break;

        case 'urma/fedex/fedex_dropoff_type':
            $options = array(
                'REGULAR_PICKUP' => Mage::helper('udropship')->__('Regular Pickup'),
                'REQUEST_COURIER' => Mage::helper('udropship')->__('Request Courier'),
                'DROP_BOX' => Mage::helper('udropship')->__('Drop Box'),
                'BUSINESS_SERVICE_CENTER' => Mage::helper('udropship')->__('Business Service Center'),
                'STATION' => Mage::helper('udropship')->__('Station'),
            );
            break;

        case 'urma/fedex/fedex_service_type':
            break;

        case 'urma/fedex/fedex_packaging_type':
            break;

        case 'urma/fedex/fedex_label_stock_type':
            $options = array(
                'PAPER_4X6' => Mage::helper('udropship')->__('PDF: Paper 4x6'),
                'PAPER_4X8' => Mage::helper('udropship')->__('PDF: Paper 4x8'),
                'PAPER_4X9' => Mage::helper('udropship')->__('PDF: Paper 4x9'),
                'PAPER_7X4.75' => Mage::helper('udropship')->__('PDF: Paper 7x4.75'),
                'PAPER_8.5X11_BOTTOM_HALF_LABEL' => Mage::helper('udropship')->__('PDF: Paper 8.5x11 Bottom Half Label'),
                'PAPER_8.5X11_TOP_HALF_LABEL' => Mage::helper('udropship')->__('PDF: Paper 8.5x11 Top Half Label'),

                'STOCK_4X6' => Mage::helper('udropship')->__('EPL: Stock 4x6'),
                'STOCK_4X6.75_LEADING_DOC_TAB' => Mage::helper('udropship')->__('EPL: Stock 4x6.75 Leading Doc Tab'),
                'STOCK_4X6.75_TRAILING_DOC_TAB' => Mage::helper('udropship')->__('EPL: Stock 4x6.75 Trailing Doc Tab'),
                'STOCK_4X8' => Mage::helper('udropship')->__('EPL: Stock 4x8'),
                'STOCK_4X9_LEADING_DOC_TAB' => Mage::helper('udropship')->__('EPL: Stock 4x9 Leading Doc Tab'),
                'STOCK_4X9_TRAILING_DOC_TAB' => Mage::helper('udropship')->__('EPL: Stock 4x9 Trailing Doc Tab'),
            );
            break;

        case 'urma/fedex/fedex_signature_option':
            $options = array(
                'NO_SIGNATURE_REQUIRED' => 'No Signature Required',
                'SERVICE_DEFAULT' => 'Default Appropriate Signature Option',
                'DIRECT' => 'Direct',
                'INDIRECT' => 'Indirect',
                'ADULT' => 'Adult',
            );
            break;

        case 'urma/fedex/fedex_notify_on':
            $options = array(
                ''  => '* None *',
                'shipment'  => 'Shipment',
                'exception' => 'Exception',
                'delivery'  => 'Delivery',
            );
            break;

        case 'urma/endicia/endicia_label_type':
            $options = array(
                'Default'=>'Default',
                'CertifiedMail'=>'CertifiedMail',
                'DestinationConfirm'=>'DestinationConfirm',
                //'International'=>'International',
            );
            break;

        case 'urma/endicia/endicia_label_size':
            $options = array(
                '4X6'=>'4X6',
                '4X5'=>'4X5',
                '4X4.5'=>'4X4.5',
                'DocTab'=>'DocTab',
                '6x4'=>'6x4',
            );
            break;
        case 'urma/endicia/endicia_mail_class':
            $options = array(
                'FirstClassMailInternational'=>'First-Class Mail International',
                'PriorityMailInternational'=>'Priority Mail International',
                'ExpressMailInternational'=>'Express Mail International',
                'Express'=>'Express Mail',
                'First'=>'First-Class Mail',
                'LibraryMail'=>'Library Mail',
                'MediaMail'=>'Media Mail',
                'ParcelPost'=>'Parcel Post',
                'ParcelSelect'=>'Parcel Select',
                'Priority'=>'Priority Mail',
            );
            break;
        case 'urma/endicia/endicia_mailpiece_shape':
            $options = array(
                'Card'=>'Card',
                'Letter'=>'Letter',
                'Flat'=>'Flat',
                'Parcel'=>'Parcel',
                'FlatRateBox'=>'FlatRateBox',
                'FlatRateEnvelope'=>'FlatRateEnvelope',
                'IrregularParcel'=>'IrregularParcel',
                'LargeFlatRateBox'=>'LargeFlatRateBox',
                'LargeParcel'=>'LargeParcel',
                'OversizedParcel'=>'OversizedParcel',
                'SmallFlatRateBox'=>'SmallFlatRateBox',
            );
            break;

        case 'urma/endicia/endicia_insured_mail':
            $options = array(
                'OFF' => 'No Insurance',
                'ON'  => 'USPS Insurance',
                'UspsOnline' => 'USPS Online Insurance',
                'Endicia' => 'Endicia Insurance',
            );
            break;

        case 'urma/endicia/endicia_customs_form_type':
            $options = array(
                'Form2976' => 'Form 2976 (same as CN22)',
                'Form2976A' => 'Form 2976A (same as CP72)',
            );
            break;

        case 'urma/ups/ups_pickup':
            $options = array(
                '' => '* Default',
                '01' => 'Daily Pickup',
                '03' => 'Customer Counter',
                '06' => 'One Time Pickup',
                '07' => 'On Call Air',
                '11' => 'Suggested Retail',
                '19' => 'Letter Center',
                '20' => 'Air Service Center',
            );
            break;

        case 'urma/ups/ups_container':
            $options = array(
                '' => '* Default',
                '00' => 'Customer Packaging',
                '01' => 'UPS Letter Envelope',
                '03' => 'UPS Tube',
                '21' => 'UPS Express Box',
                '24' => 'UPS Worldwide 25 kilo',
                '25' => 'UPS Worldwide 10 kilo',
            );
            break;

        case 'urma/ups/ups_dest_type':
            $options = array(
                '' => '* Default',
                '01' => 'Residential',
                '02' => 'Commercial',
            );
            break;

        case 'urma/ups/ups_delivery_confirmation':
            $options = array(
                '' => 'No Delivery Confirmation',
                '1' => 'Delivery Confirmation',
                '2' => 'Delivery Confirmation Signature Required',
                '3' => 'Delivery Confirmation Adult Signature Required',
            );
            break;

        case 'urma/ups/ups_shipping_method_combined':
            $usa = Mage::helper('usa');
            $options = array(
                'UPS CGI' => array(
                    '1DM'    => Mage::helper('udropship')->__('Next Day Air Early AM'),
                    '1DML'   => Mage::helper('udropship')->__('Next Day Air Early AM Letter'),
                    '1DA'    => Mage::helper('udropship')->__('Next Day Air'),
                    '1DAL'   => Mage::helper('udropship')->__('Next Day Air Letter'),
                    '1DAPI'  => Mage::helper('udropship')->__('Next Day Air Intra (Puerto Rico)'),
                    '1DP'    => Mage::helper('udropship')->__('Next Day Air Saver'),
                    '1DPL'   => Mage::helper('udropship')->__('Next Day Air Saver Letter'),
                    '2DM'    => Mage::helper('udropship')->__('2nd Day Air AM'),
                    '2DML'   => Mage::helper('udropship')->__('2nd Day Air AM Letter'),
                    '2DA'    => Mage::helper('udropship')->__('2nd Day Air'),
                    '2DAL'   => Mage::helper('udropship')->__('2nd Day Air Letter'),
                    '3DS'    => Mage::helper('udropship')->__('3 Day Select'),
                    'GND'    => Mage::helper('udropship')->__('Ground'),
                    'GNDCOM' => Mage::helper('udropship')->__('Ground Commercial'),
                    'GNDRES' => Mage::helper('udropship')->__('Ground Residential'),
                    'STD'    => Mage::helper('udropship')->__('Canada Standard'),
                    'XPR'    => Mage::helper('udropship')->__('Worldwide Express'),
                    'WXS'    => Mage::helper('udropship')->__('Worldwide Express Saver'),
                    'XPRL'   => Mage::helper('udropship')->__('Worldwide Express Letter'),
                    'XDM'    => Mage::helper('udropship')->__('Worldwide Express Plus'),
                    'XDML'   => Mage::helper('udropship')->__('Worldwide Express Plus Letter'),
                    'XPD'    => Mage::helper('udropship')->__('Worldwide Expedited'),
                ),
                'UPS XML' => array(
                    '01' => Mage::helper('udropship')->__('UPS Next Day Air'),
                    '02' => Mage::helper('udropship')->__('UPS Second Day Air'),
                    '03' => Mage::helper('udropship')->__('UPS Ground'),
                    '07' => Mage::helper('udropship')->__('UPS Worldwide Express'),
                    '08' => Mage::helper('udropship')->__('UPS Worldwide Expedited'),
                    '11' => Mage::helper('udropship')->__('UPS Standard'),
                    '12' => Mage::helper('udropship')->__('UPS Three-Day Select'),
                    '13' => Mage::helper('udropship')->__('UPS Next Day Air Saver'),
                    '14' => Mage::helper('udropship')->__('UPS Next Day Air Early A.M.'),
                    '54' => Mage::helper('udropship')->__('UPS Worldwide Express Plus'),
                    '59' => Mage::helper('udropship')->__('UPS Second Day Air A.M.'),
                    '65' => Mage::helper('udropship')->__('UPS Saver'),

                    '82' => Mage::helper('udropship')->__('UPS Today Standard'),
                    '83' => Mage::helper('udropship')->__('UPS Today Dedicated Courrier'),
                    '84' => Mage::helper('udropship')->__('UPS Today Intercity'),
                    '85' => Mage::helper('udropship')->__('UPS Today Express'),
                    '86' => Mage::helper('udropship')->__('UPS Today Express Saver'),
                ),
            );
            break;

        default:
            Mage::throwException(Mage::helper('udropship')->__('Invalid request for source options: '.$this->getPath()));
        }

        if ($selector) {
            $options = array(''=>Mage::helper('udropship')->__('* Please select')) + $options;
        }

        return $options;
    }

}
