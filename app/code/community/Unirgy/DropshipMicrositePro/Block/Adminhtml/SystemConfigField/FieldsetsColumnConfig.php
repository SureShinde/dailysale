<?php

class Unirgy_DropshipMicrositePro_Block_Adminhtml_SystemConfigField_FieldsetsColumnConfig extends Unirgy_Dropship_Block_Adminhtml_SystemConfigFormField_FieldContainer
{
    public function getEditFieldsConfig()
    {
        return Mage::helper('udmspro')->getRegistrationFieldsConfig();
    }
}
