<?php

class Bronto_Order_Model_System_Config_Source_Price
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('bronto_order');
        return array(
            'base' => $helper->__('Base Price'),
            'display' => $helper->__('Display Price'),
        );
    }
}
