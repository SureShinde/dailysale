<?php

/**
 * Deals Rotation
 *
 * @category   Fiuze
 * @package    Fiuze_Deals
 * @author     Webinse Team <info@webinse.com>
 */
class Fiuze_Deals_Model_System_Config_Source_Category
{

    public function toOptionArray()
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
        return $optionArray;
    }

}
