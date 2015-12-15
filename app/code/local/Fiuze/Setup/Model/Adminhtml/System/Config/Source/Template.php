<?php

class Fiuze_Setup_Model_Adminhtml_System_Config_Source_Template{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(){
        return array(
            array('value' => 'fiuze/newslettersubscribe/product/list.phtml',
                'label' => Mage::helper('adminhtml')->__('list.phtml')),
            array('value' => 'fiuze/newslettersubscribe/product/list-email.phtml',
                'label' => Mage::helper('adminhtml')->__('list-email.phtml')),
        );
    }

}