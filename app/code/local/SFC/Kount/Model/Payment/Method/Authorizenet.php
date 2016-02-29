<?php
// @codingStandardsIgnoreStart
/**
 * StoreFront Consulting Kount Magento Extension
 *
 * PHP version 5
 *
 * @category  SFC
 * @package   SFC_Kount
 * @copyright 2009-2015 StoreFront Consulting, Inc. All Rights Reserved.
 *
 */
// @codingStandardsIgnoreEnd

/**
 * Class SFC_Kount_Model_Payment_Method_Authorizenet
 */
class SFC_Kount_Model_Payment_Method_Authorizenet extends Mage_Paygate_Model_Authorizenet
{

    /**
     * It sets card`s data into additional information of payment model
     *
     * @param Mage_Paygate_Model_Authorizenet_Result|Varien_Object $response
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Varien_Object
     */
    protected function _registerCard(Varien_Object $response, Mage_Sales_Model_Order_Payment $payment)
    {
        // Call parent first
        $card = parent::_registerCard($response, $payment);

        // Now save additional AVS and CVV responses
        $card->setData('cc_avs_result_code', $response->getData('avs_result_code'));
        $card->setData('cc_response_code', $response->getData('card_code_response_code'));
        $payment->setCcAvsStatus($response->getData('avs_result_code'));
        $payment->setCcCidStatus($response->getData('card_code_response_code'));

        return $card;
    }

}
