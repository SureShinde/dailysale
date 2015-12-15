<?php

/**
 * @package     Bronto\Reviews
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Reviews_Model_System_Config_Source_Orderstatus
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $statArray = array();
        $status = Mage::getModel('sales/order_config')->getStatuses();
        foreach ($status as $value => $label) {
            $statArray[] = array(
                'value' => $value,
                'label' => $label
            );
        }
        return $statArray;
        
        // These are Order States not Status'
//        return array(
//            array('value' => 'new', 'label' => Mage::helper('adminhtml')->__('New Order')),
//            array('value' => 'pending_payment', 'label' => Mage::helper('adminhtml')->__('Pending Payment')),
//            array('value' => 'processing', 'label' => Mage::helper('adminhtml')->__('Processing')),
//            array('value' => 'complete', 'label' => Mage::helper('adminhtml')->__('Complete')),
//            array('value' => 'closed', 'label' => Mage::helper('adminhtml')->__('Closed')),
//            array('value' => 'canceled', 'label' => Mage::helper('adminhtml')->__('Cancelled')),
//            array('value' => 'holded', 'label' => Mage::helper('adminhtml')->__('On Hold')),
//            array('value' => 'payment_review', 'label' => Mage::helper('adminhtml')->__('Payment Review')),
//        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $status = Mage::getModel('sales/order_status')->getCollection();
        $statArray = array();
        foreach ($status as $stat) {
            $statArray[] = array(
                $stat->getStatus() => $stat->getLabel()
            );
        }
        return $statArray;
    }
}
