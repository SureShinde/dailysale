<?php

/**
 * Used in creating options for Display cart or display checkout config value selection
 *
 */
class MageWorx_InstantCart_Model_System_Config_Source_Displayoption
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Display checkout')),
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Display cart')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            0 => Mage::helper('adminhtml')->__('Display cart'),
            1 => Mage::helper('adminhtml')->__('Display checkout'),
        );
    }

}