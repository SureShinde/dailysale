<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author      Webinse Team <info@webinse.com>
 */
class Fiuze_Deals_Model_System_Config_Source_Status{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(){
        return array(
            array('value' => 0, 'label' => Mage::helper('adminhtml')->__('Disabled')),
            array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Enabled')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(){
        return array(
            '1' => Mage::helper('adminhtml')->__('Enabled'),
            '0' => Mage::helper('adminhtml')->__('Disabled'),
        );
    }

}
