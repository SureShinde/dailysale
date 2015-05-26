<?php
/**
 * HTML select element block with category groups options
 */
class Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Checkboxgroup extends Mage_Core_Block_Html_Select
{

    public function getName(){
        return 'groups[bestsellers_settings_grp][fields][general][value][#{_id}][checkbox]';
    }

    public function calcOptionHash($optionValue)
    {
        return sprintf('%u', crc32($this->getName() . $this->getId() . $optionValue));
    }

    /**
     * Render HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_beforeToHtml()) {
            return '';
        }
        $selectedHtml .= '#{checkbox}' ;
        $html = '<div style="text-align: center;">';
        $html .= '<input name="' . $this->getName() . '" type="checkbox" onclick="clickCheckbox(this, #{_id})" '.$selectedHtml.'>';
        $html .= '</div>';
        return $html;
    }

    /**
     * HTML ID of the element
     *
     * @return string
     */
    public function getId()
    {
        return $this->getData('id');
    }
}
