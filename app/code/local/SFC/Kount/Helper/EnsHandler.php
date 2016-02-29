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

class SFC_Kount_Helper_EnsHandler extends Mage_Core_Helper_Abstract
{
    /**
     * Ip Addresses
     */
    const IPADDRESS_1 = '64.128.91.251';
    const IPADDRESS_2 = '209.81.12.251';

    /**
     * Process event
     * @param array $aEvent Event
     * @return boolean
     * @throws Exception
     */
    public function handleEvent($aEvent)
    {
        /** @var SFC_Kount_Helper_Data $helper */
        $helper = Mage::helper('kount');
        // Log event details
        Mage::log('============================', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('Kount extension version: ' . $helper->getExtensionVersion(), Zend_Log::INFO,
            SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('==== ENS Event Details =====', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('Name: ' . $aEvent['name'], Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('order_number: ' . $aEvent['key']['_attribute']['order_number'], Zend_Log::INFO,
            SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('transaction_id: ' . $aEvent['key']['_value'], Zend_Log::INFO,
            SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('old_value: ', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log($aEvent['old_value'], Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('new_value: ', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log($aEvent['new_value'], Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('agent: ' . $aEvent['agent'], Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('occurred: ' . $aEvent['occurred'], Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
        Mage::log('============================', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Validate event
        if (!isset($aEvent['name'])) {
            Mage::throwException('Invalid Event name.');
        }

        // What should we do
        switch ($aEvent['name']) {

            // -- DMC
            case 'DMC_EMAIL_ADD':
            case 'DMC_EMAIL_EDIT':
            case 'DMC_EMAIL_DELETE':
            case 'DMC_CARD_ADD':
            case 'DMC_CARD_EDIT':
            case 'DMC_CARD_DELETE':
            case 'DMC_ADDRESS_ADD':
            case 'DMC_ADDRESS_EDIT':
            case 'DMC_ADDRESS_DELETE':
                // -- -- Log
                Mage::log('DMC event received, but nothing to do.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
                break;

            case 'WORKFLOW_QUEUE_ASSIGN':
            case 'WORKFLOW_NOTES_ADD':
            case 'WORKFLOW_REEVALUATE':
            case 'RISK_CHANGE_SCOR':
            case 'RISK_CHANGE_REPLY':
            case 'SPECIAL_ALERT_TRANSACTION':
            case 'RISK_CHANGE_VELO':
            case 'RISK_CHANGE_VMAX':
            case 'RISK_CHANGE_GEOX':
            case 'RISK_CHANGE_NETW':
            case 'RISK_CHANGE_REAS':
                Mage::log('DMC event '.$aEvent['name'].' received, ignored.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);
                break;

            case 'WORKFLOW_STATUS_EDIT':
                // -- -- Get order
                $oOrder = $this->loadOrder($aEvent);
                // -- -- Add comment to order
                $sComment = 'Kount ENS Notification: Modify status of an order by agent.';
                $oOrder->addStatusHistoryComment($sComment);
                $oOrder->save();
                // Set new RIS Response on order
                $oOrder->setData('kount_ris_response', $aEvent['new_value']);
                $oOrder->save();
                // Handle status change on Magento order
                $this->handleKountStatusChange($aEvent, $oOrder);
                break;
        }

        return true;
    }

    /**
     * Load an order
     *
     * @param array $aEvent Event
     * @return Mage_Sales_Model_Order Order with passed in increment Id
     * @throws Exception
     */
    protected function loadOrder($aEvent)
    {
        // -- -- Get order Id
        if (!isset($aEvent['key']['_attribute']['order_number'])) {
            Mage::throwException('Invalid Order number.');
        }
        $sOrderId = $aEvent['key']['_attribute']['order_number'];

        // -- -- Get order
        /** @var Mage_Sales_Model_Order $oOrder */
        $oOrder = Mage::getModel('sales/order');
        $oOrder = $oOrder->loadByIncrementId($sOrderId);
        if (!strlen($oOrder->getEntityId())) {
            Mage::throwException("Unable to locate order for: {$sOrderId}");
        }

        // Ensure that the transaction id matches, if not we will ignore this ENS event
        if (!isset($aEvent['key']['_value'])) {
            Mage::throwException('Invalid Transaction ID.');
        }
        // Get ris info from order payment
        $risInfo = get_object_vars(json_decode($oOrder->getPayment()->getAdditionalInformation('ris_additional_info')));
        // Get trans id from order
        if (!isset($risInfo['TRAN'])) {
            Mage::throwException('Invalid Transaction ID.');
        }
        $orderTransactionId = $risInfo['TRAN'];
        if ($aEvent['key']['_value'] != $orderTransactionId) {
            Mage::throwException('Transaction ID does not match order, event must be for discarded version of order!');
        }

        return $oOrder;
    }

    /**
     * Process event 'WORKFLOW_STATUS_EDIT' and other events indicating Kount RIS status changes
     *
     * @param array $aEvent Event
     * @param Mage_Sales_Model_Order $oOrder Magento order model on which to operate
     * @return boolean
     * @throws Exception
     */
    protected function handleKountStatusChange($aEvent, $oOrder)
    {
        $helper = Mage::helper('kount');
        /** @var SFC_Kount_Helper_Order $orderHelper */
        $orderHelper = Mage::helper('kount/order');

        Mage::log('Running handleStatusChange()...', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

        // Detect and handle situation where order is newly declined by Kount, previously at review status
        if ($aEvent['new_value'] == SFC_Kount_Helper_Ris::RESPONSE_DECLINE &&
            ($aEvent['old_value'] == SFC_Kount_Helper_Ris::RESPONSE_REVIEW ||
            $aEvent['old_value'] == SFC_Kount_Helper_Ris::RESPONSE_ESCALATE)
        ) {
            // Log
            Mage::log('Kount status transitioned from review to decline.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            // Check if pre-hold status & state were saved
            // If not, we won't do anything here
            if ($oOrder->getHoldBeforeState() == null || $oOrder->getHoldBeforeState() == null) {
                Mage::log('Pre-hold order state / status not preserved.', Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

                return;
            }

            // Move order from Hold to previous status
            // Un-hold order if possible
            if ($oOrder->canUnhold()){
                $oOrder->unhold();
            }
            // Mage::helper('kount')->restorePreHoldOrderStatus($oOrder);

            // Now cancel order or issue refund or fall back on marking order as 'Kount Decline'
            $orderHelper->handleDecline($oOrder);
        }

        // Detect and handle situation where order is newly approved, previously at review status
        if ($aEvent['new_value'] == SFC_Kount_Helper_Ris::RESPONSE_APPROVE &&
            ($aEvent['old_value'] == SFC_Kount_Helper_Ris::RESPONSE_REVIEW ||
            $aEvent['old_value'] == SFC_Kount_Helper_Ris::RESPONSE_ESCALATE)
        ) {
            // Log
            Mage::log('Kount status transitioned from review to allow.', Zend_Log::INFO, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

            // Check if pre-hold status & state were saved
            // If not, we won't do anything here
            if ($oOrder->getHoldBeforeState() == null || $oOrder->getHoldBeforeState() == null) {
                Mage::log('Pre-hold order state / status not preserved.', Zend_Log::ERR, SFC_Kount_Helper_Data::KOUNT_LOG_FILE);

                return;
            }

            // Move order from Hold to previous status
            $orderHelper->restorePreHoldOrderStatus($oOrder);

        }

    }

    /**
     * Validate store for merchant Id
     *
     * @param string $sMerchantId Merchant Id
     * @return boolean
     */
    public function validateStoreForMerchantId($sMerchantId)
    {
        // Check admin first
        $sTest = Mage::getStoreConfig('kount/account/merchantnum', 0);
        if (intval($sTest) == intval($sMerchantId)) {
            return true;
        }

        // All stores
        foreach (Mage::app()->getStores() as $iStoreId => $sVal) {
            $sTest = Mage::getStoreConfig('kount/account/merchantnum', $iStoreId);
            if (intval($sTest) == intval($sMerchantId)) {
                return true;
            }
        }

        return false;
    }

}
