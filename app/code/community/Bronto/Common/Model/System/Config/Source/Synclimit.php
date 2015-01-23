<?php

/**
 * @package     Bronto\Common
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Model_System_Config_Source_Synclimit
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            100   => 100,
            250   => 250,
            500   => 500,
            1000  => 1000,
            5000  => 5000,
        );
    }
}
