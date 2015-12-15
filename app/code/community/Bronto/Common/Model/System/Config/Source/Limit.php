<?php

/**
 * @package     Bronto\Common
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Model_System_Config_Source_Limit
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            10    => 10,
            25    => 25,
            50    => 50,
            100   => 100,
            250   => 250,
            500   => 500,
            1000  => 1000,
        );
    }
}
