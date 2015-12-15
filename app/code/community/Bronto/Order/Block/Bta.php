<?php

/**
 * @package   Bronto\Newsletter
 * @copyright 2011-2013 Bronto Software, Inc.
 * @deprecated
 */
class Bronto_Order_Block_Bta extends Mage_Core_Block_Template
{
    /**
     * Generate BTA Key For Script
     *
     * @return string
     */
    public function getKey()
    {
        return Mage::helper('bronto_order')->getTidKey();
    }
}
