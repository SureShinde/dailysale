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
                'global' => $hlp->__('Global'),
                'vendor' => $hlp->__('Vendor'),
            );
            break;

        case 'vendor_rma_grid_sortdir':
            $options = array(
                'asc' => $hlp->__('Ascending'),
                'desc' => $hlp->__('Descending'),
            );
            break;

        case 'vendor_rma_grid_sortby':
            $options = array(
                'increment_id' => $hlp->__('RMA ID'),
                'rma_date' => $hlp->__('RMA Date'),
                'udropship_status' => $hlp->__('RMA Status'),
                'rma_reason' => $hlp->__('Reason to return'),
            );
            break;

        case 'rma_use_address':
            $options = array(
                'origin' => $hlp->__('Origin'),
                'custom' => $hlp->__('Custom'),
            );
            break;

        case 'urma/fedex/fedex_dropoff_type':
            $options = array(
                'REGULAR_PICKUP' => $hlp->__('Regular Pickup'),
                'REQUEST_COURIER' => $hlp->__('Request Courier'),
                'DROP_BOX' => $hlp->__('Drop Box'),
                'BUSINESS_SERVICE_CENTER' => $hlp->__('Business Service Center'),
                'STATION' => $hlp->__('Station'),
            );
            break;

        case 'urma/fedex/fedex_service_type':
            break;

        case 'urma/fedex/fedex_packaging_type':
            break;

        case 'urma/fedex/fedex_label_stock_type':
            $options = array(
                'PAPER_4X6' => $hlp->__('PDF: Paper 4x6'),
                'PAPER_4X8' => $hlp->__('PDF: Paper 4x8'),
                'PAPER_4X9' => $hlp->__('PDF: Paper 4x9'),
                'PAPER_7X4.75' => $hlp->__('PDF: Paper 7x4.75'),
                'PAPER_8.5X11_BOTTOM_HALF_LABEL' => $hlp->__('PDF: Paper 8.5x11 Bottom Half Label'),
                'PAPER_8.5X11_TOP_HALF_LABEL' => $hlp->__('PDF: Paper 8.5x11 Top Half Label'),

                'STOCK_4X6' => $hlp->__('EPL: Stock 4x6'),
                'STOCK_4X6.75_LEADING_DOC_TAB' => $hlp->__('EPL: Stock 4x6.75 Leading Doc Tab'),
                'STOCK_4X6.75_TRAILING_DOC_TAB' => $hlp->__('EPL: Stock 4x6.75 Trailing Doc Tab'),
                'STOCK_4X8' => $hlp->__('EPL: Stock 4x8'),
                'STOCK_4X9_LEADING_DOC_TAB' => $hlp->__('EPL: Stock 4x9 Leading Doc Tab'),
                'STOCK_4X9_TRAILING_DOC_TAB' => $hlp->__('EPL: Stock 4x9 Trailing Doc Tab'),
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
                    '1DM'    => $usa->__('Next Day Air Early AM'),
                    '1DML'   => $usa->__('Next Day Air Early AM Letter'),
                    '1DA'    => $usa->__('Next Day Air'),
                    '1DAL'   => $usa->__('Next Day Air Letter'),
                    '1DAPI'  => $usa->__('Next Day Air Intra (Puerto Rico)'),
                    '1DP'    => $usa->__('Next Day Air Saver'),
                    '1DPL'   => $usa->__('Next Day Air Saver Letter'),
                    '2DM'    => $usa->__('2nd Day Air AM'),
                    '2DML'   => $usa->__('2nd Day Air AM Letter'),
                    '2DA'    => $usa->__('2nd Day Air'),
                    '2DAL'   => $usa->__('2nd Day Air Letter'),
                    '3DS'    => $usa->__('3 Day Select'),
                    'GND'    => $usa->__('Ground'),
                    'GNDCOM' => $usa->__('Ground Commercial'),
                    'GNDRES' => $usa->__('Ground Residential'),
                    'STD'    => $usa->__('Canada Standard'),
                    'XPR'    => $usa->__('Worldwide Express'),
                    'WXS'    => $usa->__('Worldwide Express Saver'),
                    'XPRL'   => $usa->__('Worldwide Express Letter'),
                    'XDM'    => $usa->__('Worldwide Express Plus'),
                    'XDML'   => $usa->__('Worldwide Express Plus Letter'),
                    'XPD'    => $usa->__('Worldwide Expedited'),
                ),
                'UPS XML' => array(
                    '01' => $usa->__('UPS Next Day Air'),
                    '02' => $usa->__('UPS Second Day Air'),
                    '03' => $usa->__('UPS Ground'),
                    '07' => $usa->__('UPS Worldwide Express'),
                    '08' => $usa->__('UPS Worldwide Expedited'),
                    '11' => $usa->__('UPS Standard'),
                    '12' => $usa->__('UPS Three-Day Select'),
                    '13' => $usa->__('UPS Next Day Air Saver'),
                    '14' => $usa->__('UPS Next Day Air Early A.M.'),
                    '54' => $usa->__('UPS Worldwide Express Plus'),
                    '59' => $usa->__('UPS Second Day Air A.M.'),
                    '65' => $usa->__('UPS Saver'),

                    '82' => $usa->__('UPS Today Standard'),
                    '83' => $usa->__('UPS Today Dedicated Courrier'),
                    '84' => $usa->__('UPS Today Intercity'),
                    '85' => $usa->__('UPS Today Express'),
                    '86' => $usa->__('UPS Today Express Saver'),
                ),
            );
            break;

        default:
            Mage::throwException($hlp->__('Invalid request for source options: '.$this->getPath()));
        }

        if ($selector) {
            $options = array(''=>$hlp->__('* Please select')) + $options;
        }

        return $options;
    }

}
