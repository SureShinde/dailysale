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
 * @package    Unirgy_DropshipSplit
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

/**
* Currently not in use
*/
class Unirgy_DropshipTierShipping_Model_Source extends Unirgy_Dropship_Model_Source_Abstract
{
    const CM_MAX_FIRST_ADDITIONAL = 1;
    const CM_SUM_FIRST_ADDITIONAL = 2;
    const CM_MULTIPLY_FIRST       = 3;
    const CM_MAX_FIRST = 4;
    const CM_SUM_FIRST = 5;

    const CT_SEPARATE = 1;
    const CT_BASE_PLUS_ZONE_PERCENT = 2;
    const CT_BASE_PLUS_ZONE_FIXED   = 3;

    const FL_VENDOR_BASE = 1;
    const FL_VENDOR_DEFAULT = 2;
    const FL_TIER = 2;

    const USE_RATES_V1 = 0;
    const USE_RATES_V1_SIMPLE = 1;
    const USE_RATES_V2 = 2;
    const USE_RATES_V2_SIMPLE = 3;
    const USE_RATES_V2_SIMPLE_COND = 4;

    const SIMPLE_COND_FULLWEIGHT = 'full_weight';
    const SIMPLE_COND_SUBTOTAL = 'subtotal';
    const SIMPLE_COND_TOTALQTY = 'total_qty';

    public function toOptionHash($selector=false)
    {
        $hlp = Mage::helper('udropship');
        $hlpv = Mage::helper('udtiership');

        switch ($this->getPath()) {

        case 'carriers/udtiership/additional_calculation_type':
        case 'carriers/udtiership/cost_calculation_type':
        case 'carriers/udtiership/handling_calculation_type':
            $options = array(
                self::CT_SEPARATE => $hlpv->__('Separate per customer shipclass'),
                self::CT_BASE_PLUS_ZONE_PERCENT => $hlpv->__('Base plus percent per customer shipclass'),
                self::CT_BASE_PLUS_ZONE_FIXED   => $hlpv->__('Base plus fixed per customer shipclass'),
            );
            break;
        case 'carriers/udtiership/calculation_method':
            $options = array(
                self::CM_MAX_FIRST_ADDITIONAL => $hlpv->__('Max first item other additional'),
                self::CM_MAX_FIRST => $hlpv->__('Max first item (discard qty)'),
                self::CM_SUM_FIRST_ADDITIONAL => $hlpv->__('Sum first item other additional'),
                self::CM_SUM_FIRST => $hlpv->__('Sum first item (discard qty)'),
                self::CM_MULTIPLY_FIRST       => $hlpv->__('Multiply first item (additional not used)'),
            );
            break;

        case 'carriers/udtiership/fallback_lookup':
            $options = array(
                self::FL_VENDOR_BASE => $hlpv->__('Vendor up to BASE'),
                self::FL_VENDOR_DEFAULT => $hlpv->__('Vendor up to DEFAULT'),
                self::FL_TIER => $hlpv->__('Vendor/Global by tier'),
            );
            break;

        case 'carriers/udtiership/handling_apply_method':
            $options = array(
                'none'      => 'None',
                'fixed'     => 'Fixed Per Category',
                'fixed_max' => 'Max Fixed',
                'percent'   => 'Percent',
            );
            break;

        case 'carriers/udtiership/use_simple_rates':
           $options = array(
               self::USE_RATES_V1 => $hlpv->__('V1 Rates'),
               self::USE_RATES_V1_SIMPLE => $hlpv->__('V1 Simple Rates'),
               self::USE_RATES_V2 => $hlpv->__('V2 By Category/VendorClass First/Additional/Handling Rates'),
               self::USE_RATES_V2_SIMPLE => $hlpv->__('V2 Simple First/Additional Rates'),
               self::USE_RATES_V2_SIMPLE_COND => $hlpv->__('V2 Simple Conditional Rates'),
           );
           break;

        case 'simple_condition':
            $options = array(
                self::SIMPLE_COND_FULLWEIGHT => $hlpv->__('Full Weight'),
                self::SIMPLE_COND_SUBTOTAL => $hlpv->__('Subtotal'),
                self::SIMPLE_COND_TOTALQTY => $hlpv->__('Total Qty'),
            );
            break;

        case 'tiership_delivery_type_selector':
        case 'carriers/udtiership/delivery_type_selector':
            $selector = true;
            $options = Mage::getResourceModel('udtiership/deliveryType_collection')->toOptionHash();
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