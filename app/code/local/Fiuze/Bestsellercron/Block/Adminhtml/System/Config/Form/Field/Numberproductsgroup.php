<?php
/**
 * HTML select element block
 * Class Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Timeperiodgroup
 */
class Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Numberproductsgroup extends Mage_Core_Block_Abstract
{

    protected $_options = array();

    /**
     * Get options of the element
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Set options for the HTML select
     *
     * @param array $options
     * @return Mage_Core_Block_Html_Select
     */
    public function setOptions($options)
    {
        $this->_options = $options;
        return $this;
    }

    /**
     * Add an option to HTML select
     *
     * @param string $value  HTML value
     * @param string $label  HTML label
     * @param array  $params HTML attributes
     * @return Mage_Core_Block_Html_Select
     */
    public function addOption($value, $label, $params=array())
    {
        $this->_options[] = array('value' => $value, 'label' => $label, 'params' => $params);
        return $this;
    }

    /**
     * Set element's HTML ID
     *
     * @param string $id ID
     * @return Mage_Core_Block_Html_Select
     */
    public function setId($id)
    {
        $this->setData('id', $id);
        return $this;
    }

    /**
     * Set element's CSS class
     *
     * @param string $class Class
     * @return Mage_Core_Block_Html_Select
     */
    public function setClass($class)
    {
        $this->setData('class', $class);
        return $this;
    }

    /**
     * Set element's HTML title
     *
     * @param string $title Title
     * @return Mage_Core_Block_Html_Select
     */
    public function setTitle($title)
    {
        $this->setData('title', $title);
        return $this;
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

    /**
     * CSS class of the element
     *
     * @return string
     */
    public function getClass()
    {
        return $this->getData('class');
    }

    /**
     * Returns HTML title of the element
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getData('title');
    }

    /**
     * Render HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        //$html = '<input type="hidden" id="' . $this->getId() . '" />';
        $html = '<div '.$this->getExtraParams().' style="text-align: center;">';
        $selectedHtml = '#{option_extra_attr_' . $this->calcOptionHash('checkbox') . '}' ;
        $html .= '<input class="checkbox_products" name="' . $this->getName() . '[checkbox]' . '" type="checkbox" onclick="clickCheckboxNumberOfProducts(this, #{_id})" '.$selectedHtml.'>';

        $name = $this->getInputName() . '[count_products]';
        $html.= '&nbsp;:&nbsp;<input type="text" name="'. $name . '"  style="width:70px" value="' . '#{option_extra_attr_' . $this->calcOptionHash('count_products') . '}' .'">';
        $html.= '</input>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Alias for toHtml()
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->toHtml();
    }

    /**
     * Calculate CRC32 hash for option value
     *
     * @param string $optionValue Value of the option
     * @return string
     */
    public function calcOptionHash($numArray)
    {
        $name = 'groups[bestsellers_settings_grp][fields][general][value][#{_id}][number_products_group]'. '['.$numArray.']';
        return sprintf('%u', crc32($name . $this->getId()));
    }

    public function getName()
    {
        return $this->getInputName();
    }
}
