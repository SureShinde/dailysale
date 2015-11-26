<?php
/**
 * HTML select element block with category groups options
 */
class Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Categoryexcludegroup extends Mage_Core_Block_Html_Select
{

    public function getOptions()
    {
        $categories = Mage::getResourceModel('catalog/category_collection')
            ->addAttributeToSelect('name')
            ->getItems();

        $optionArray = array();
        foreach ($categories as $category) {
            if (!$category->getName()) {
                continue;
            }

            $optionArray[] = array(
                'label' => $category->getName(),
                'value' => $category->getId()
            );
        }
        $optionArray[0] = array(
            'label' => 'null',
            'value' => '1'
        );
        return $optionArray;
    }

    public function getName(){
        return 'groups[bestsellers_settings_grp][fields][general][value][#{_id}][category_exclude]';
    }

    public function calcOptionHash($optionValue)
    {
        return sprintf('%u', crc32($this->getName() . $this->getId() . $optionValue));
    }
}
