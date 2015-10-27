<?php
/**
 * HTML select element block with criteria groups options
 */
class Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Criteriagroup extends Mage_Core_Block_Html_Select
{

    public function getOptions()
    {
        $toOptionArray = array(
            '0' => array('label' => 'Max Profit', 'value' => 'profit'),
            '1' => array('label' => 'Revenue', 'value' => 'revenue'),
            '3' => array('label' => 'Qty', 'value' => 'qty'),
//            '4' => array('label' => 'Price', 'value' => 'price'),
        );
        return $toOptionArray;
    }
    public function getName(){
        return 'groups[bestsellers_settings_grp][fields][general][value][#{_id}][criteria]';
    }

    public function calcOptionHash($optionValue)
    {
        return sprintf('%u', crc32($this->getName() . $this->getId() . $optionValue));
    }
}
