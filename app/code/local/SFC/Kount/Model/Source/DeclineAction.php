<?php
/**
 */
class SFC_Kount_Model_Source_DeclineAction
{

    const ACTION_HOLD = 'hold';
    const ACTION_CANCEL = 'cancel';
    const ACTION_REFUND = 'refund';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::ACTION_HOLD,
                'label' => Mage::helper('kount')->__('Hold Order / Decline Status')
            ),
            array(
                'value' => self::ACTION_CANCEL,
                'label' => Mage::helper('kount')->__('Cancel Order / Void Payment')
            ),
            array(
                'value' => self::ACTION_REFUND,
                'label' => Mage::helper('kount')->__('Refund / Credit Order')
            ),
        );
    }

}
