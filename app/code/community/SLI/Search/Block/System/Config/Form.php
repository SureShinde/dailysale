<?php

/**
 * System config form
 * This form overwrites the System Config form block in order
 * to add the grid as a frontend type to forms in the system
 * config.
 *
 * @package    SLI
 * @subpackage Search
 * @author     Blue Acorn: Brys Sepulveda
 */
class SLI_Search_Block_System_Config_Form extends Mage_Adminhtml_Block_System_Config_Form
{
    /**
     * Add the additional grid type as a viable type on the form
     *
     * @return array
     */
    protected function _getAdditionalElementTypes()
    {
        $types = parent::_getAdditionalElementTypes();
        $types["version"] = Mage::getConfig()->getBlockClassName('sli_search/system_config_form_field_version');
        $types["minigrid"] = Mage::getConfig()->getBlockClassName("sli_search/system_config_form_field_minigrid");
        return $types;
    }
}
