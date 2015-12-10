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

class Unirgy_DropshipPayout_Model_Source extends Unirgy_Dropship_Model_Source_Abstract
{
    public function toOptionHash($selector=false)
    {
        $hlp = Mage::helper('udropship');
        $ptHlp = Mage::helper('udpayout');

        switch ($this->getPath()) {

        case 'payout_type':
            $options = array(
                '' => $ptHlp->__('* No Payout'),
                Unirgy_DropshipPayout_Model_Payout::TYPE_AUTO      => $ptHlp->__('Auto'),
                Unirgy_DropshipPayout_Model_Payout::TYPE_MANUAL    => $ptHlp->__('Manual'),
                Unirgy_DropshipPayout_Model_Payout::TYPE_SCHEDULED => $ptHlp->__('Scheduled'),
            );
            break;
        case 'payout_type_internal':
            $options = array(
                '' => $ptHlp->__('* No Payout'),
                Unirgy_DropshipPayout_Model_Payout::TYPE_AUTO      => $ptHlp->__('Auto'),
                Unirgy_DropshipPayout_Model_Payout::TYPE_MANUAL    => $ptHlp->__('Manual'),
                Unirgy_DropshipPayout_Model_Payout::TYPE_SCHEDULED => $ptHlp->__('Scheduled'),
                Unirgy_DropshipPayout_Model_Payout::TYPE_STATEMENT => $ptHlp->__('Statement'),
            );
            break;

        case 'payout_method':
            $options = array();
            foreach (Mage::app()->getConfig()->getNode('global/udropship/payout/method')->children() as $method) {
                $options[$method->getName()] = $ptHlp->__((string)$method->title);
            }
            break;
            
        case 'po_status_type':
            $options = array(
                'statement' => $ptHlp->__('Use Statement preferences'),
            	'payout' => $ptHlp->__('Custom'),
            );
            break;

        case 'payout_status':
        case 'po_payout_status':
            $options = array(
                Unirgy_DropshipPayout_Model_Payout::STATUS_PENDING    => $ptHlp->__('Pending'),
                Unirgy_DropshipPayout_Model_Payout::STATUS_SCHEDULED  => $ptHlp->__('Scheduled'),
                Unirgy_DropshipPayout_Model_Payout::STATUS_PROCESSING => $ptHlp->__('Processing'),
                Unirgy_DropshipPayout_Model_Payout::STATUS_HOLD       => $ptHlp->__('Hold'),
                Unirgy_DropshipPayout_Model_Payout::STATUS_PAYPAL_IPN => $ptHlp->__('Waiting for Paypal IPN'),
                Unirgy_DropshipPayout_Model_Payout::STATUS_PAID       => $ptHlp->__('Paid'),
                Unirgy_DropshipPayout_Model_Payout::STATUS_ERROR      => $ptHlp->__('Error'),
                Unirgy_DropshipPayout_Model_Payout::STATUS_CANCELED   => $ptHlp->__('Canceled'),
            );
            break;

        case 'payout_schedule_type':
            $options = $ptHlp->getPayoutSchedules('code2title');
            $options['-1'] = $hlp->__('* Use Custom');
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
