<?php

/**
 * @package   Bronto\Order
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Order_Model_System_Config_Source_Description
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            'short_description' => 'short_description',
            'description'       => 'description',
        );
    }
}
