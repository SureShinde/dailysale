<?php

/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @copyright  Copyright (c) 2010 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */
/**
 * Customer Credit extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */
class MageWorx_CustomerCredit_Block_Payment_Form extends Mage_Payment_Block_Form {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('customercredit/payment/form.phtml');
    }

   public function getCreditValue() {
        $quote  = Mage::getSingleton('checkout/cart')->getQuote();
        if (Mage::app()->getStore()->isAdmin()) {
            $quote  = Mage::getSingleton('adminhtml/sales_order_create')->getQuote();
        }
        $subtotal = $quote->getSubtotalWithDiscount();
        $address = Mage::helper('customercredit')->getSalesAddress($quote);
        $subtotal -= $address->getMwRewardpointDiscount();
        $websiteId       = Mage::app()->getWebsite()->getId();
        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();

        $productConditionsPrice = array();
        $productConditionsPrice = Mage::helper('customercredit')->checkApplyCreditsSum($quote,$customerId,$websiteId);

        $shipping = floatval($address->getShippingAmount() - $address->getShippingTaxAmount());
        $tax = floatval($address->getTaxAmount());
        $creditTotals = Mage::helper('customercredit')->getCreditTotals();
        if (count($creditTotals)<=3) {
            foreach ($creditTotals as $field) {
                switch ($field) {
                    case 'shipping':
                        $subtotal += $shipping;
                        break;
                    case 'tax':
                        $subtotal += $tax;
                        break;
                    case 'fees':
                        $subtotal += $address->getMultifeesAmount();
                        break;
                }
            }
        }
        if(sizeof($productConditionsPrice)>0) {
            $sum = array_sum($productConditionsPrice);
            $subtotal = $sum;
        }

         if((Mage::helper('customercredit')->getRealCreditValue()!=Mage::helper('customercredit')->getUsedCreditValue()) ||
            Mage::helper('customercredit')->getRealCreditValue()<$subtotal) {
                $a = (float)Mage::helper('customercredit')->getUsedCreditValue();
        }
        else {
            $a =  (float)$subtotal;
        }

        if($subtotal+$tax+$shipping-$a<Mage::getStoreConfig('mageworx_customers/customercredit_credit/min_order_amount')) {
            $a -= Mage::getStoreConfig('mageworx_customers/customercredit_credit/min_order_amount') - ($subtotal+$tax+$shipping - $a);
        }
        return $a;
    }
}