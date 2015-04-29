<?php
class Fiuze_Notifylowstock_Model_System_Config_Source_View
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = array();
        $categories = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('id')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active');

        foreach ($categories as $category)
        {
            if ($category->getIsActive()) {
                $result[]=array('value' =>$category->getId(), 'label'=>$category->getName());
            }
        }

        return $result;
    }
}