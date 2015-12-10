<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Email_Model_System_Config_Source_Sendtype
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'transactional', 'label' => Mage::helper('adminhtml')->__('Transactional')),
            array('value' => 'marketing', 'label' => Mage::helper('adminhtml')->__('Marketing')),
        );
    }
}