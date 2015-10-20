<?php
/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_Adminhtml
 * @copyright  Copyright (c) 2010 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * MageWorx Adminhtml extension
 *
 * @category   MageWorx
 * @package    MageWorx_Adminhtml
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */



class Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Legend
	extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
	protected $_dummyElement;
	protected $_fieldRenderer;
	protected $_values;



    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = "";
        $html.= $this->_getFieldHtml($element);
        return $html;
    }

    protected function _getFieldRenderer()
    {
    	if (empty($this->_fieldRenderer)) {
    		$this->_fieldRenderer = Mage::getBlockSingleton('bestsellercron/adminhtml_system_config_form_field_fieldlegend');
    	}
    	return $this->_fieldRenderer;
    }

    protected function _getFooterHtml($element)
    {
        $html = parent::_getFooterHtml($element);
        $html = '</br><hr></br>'.'<h4>' . $this->__('Legend') . '</h4>' . $html;

        return $html;
    }

    protected function _getFieldHtml($fieldset, $value = "")
    {
        $field = $fieldset->addField($value, 'label',
            array(
                'name'          => 'nameLegend',
            ))->setRenderer($this->_getFieldRenderer());

		return $field->toHtml();
    }


}
