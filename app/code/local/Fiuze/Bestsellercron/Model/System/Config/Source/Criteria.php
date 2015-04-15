<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Bestsellercron
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Bestsellercron_Model_System_Config_Source_Criteria {

    public function toOptionArray() {
        $toOptionArray = array(
            '0' => array('label' => 'Max Profit', 'value' => 'profit'),
            '1' => array('label' => 'Revenue', 'value' => 'revenue'),
            '3' => array('label' => 'Qty', 'value' => 'qty'),
        );
        
        return $toOptionArray;
    }

}

