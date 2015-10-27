<?php
/**
 * HTML input element block
 * Class Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Pricefiltergroup
 */
class Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Pricefiltergroup extends Mage_Core_Block_Abstract
{
    /**
     * Render HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $inputName = $this->getInputName();
        $columnName = $this->getColumnName();
        $column = $this->getColumn();
        //$name = $this->getInputName() . '[price_filter]';
        $html = '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
            (isset($column['class']) ? $column['class'] : 'input-text') . '"'.
            (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '/>';
        $html.= '</input>';
        return $html;
    }


}
