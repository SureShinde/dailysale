<?php

class Bronto_Order_Model_System_Config_Source_Bulk
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            5 => 5,
            10 => 10,
            25 => 25,
            50 => 50,
            100 => 100,
        );
    }
}
