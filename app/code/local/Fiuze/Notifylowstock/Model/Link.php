<?php
class Fiuze_Notifylowstock_Model_Link extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions() {
        $options = array(
            array('value'=>'0', 'label'=>Mage::helper('catalog')->__('No')),
            array('value'=>'1', 'label'=>Mage::helper('catalog')->__('Yes'))
        );
        return $options;
    }
}