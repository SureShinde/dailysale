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
 * @package    MageWorx_CustomerCredit
 * @copyright  Copyright (c) 2010 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */
 
/**
 * Customer Credit extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */
class MageWorx_CustomerCredit_Block_Adminhtml_Rules_Edit_Tab_Actions extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('current_customercredit_rule');
	
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('action_fieldset', array('legend'=>Mage::helper('salesrule')->__('Update prices using the following information')));
	$rule_type = MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_APPLY;
        if(Mage::registry('current_customercredit_rule')->getId()) {
            $rule_type = Mage::registry('current_customercredit_rule')->getRuleType();
        }
        if(Mage::app()->getRequest()->getParam('current_rule_type')) {
            $rule_type = Mage::app()->getRequest()->getParam('current_rule_type');
        }

        $fieldset->addField('is_onetime', 'select', array(
            'label'     => Mage::helper('salesrule')->__('One-time'),
            'name'      => 'is_onetime',
            'disabled'  => ($rule_type == MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_APPLY) ? true : false,
            'note'   => ($rule_type == MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_APPLY) ? "<span class='disabled'>".$this->__('Disabled')."<span>" : '',
            'options'   => array(
                '1' => Mage::helper('customercredit')->__('Yes'),
                '0' => Mage::helper('customercredit')->__('No')                
            ),
        ));

        $fieldset->addField('qty_dependent', 'select', array(
            'label'     => Mage::helper('salesrule')->__('Qty Dependent'),
            'name'      => 'qty_dependent',
            'disabled'  => ($rule_type == MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_APPLY) ? true : false,
            'note'   => ($rule_type == MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_APPLY) ? "<span class='disabled'>".$this->__('Disabled')."<span>" : '',
            'options'   => array(
                '1' => Mage::helper('customercredit')->__('Yes'),
                '0' => Mage::helper('customercredit')->__('No')                
            ),
        ));
                
        $element = $fieldset->addField('credit', 'text', array(
            'name'      => 'credit',
            'required'  => true,
            'disabled'  => ($rule_type == MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_APPLY) ? true : false,
            'note'   => ($rule_type == MageWorx_CustomerCredit_Model_Rules::CC_RULE_TYPE_APPLY) ? "<span class='disabled'>".$this->__('Disabled')."<span>" : '',
            'class'     => 'validate-not-negative-number',
            'label'     => Mage::helper('customercredit')->__('Credit Amount'),
        ));

        $element->setAfterElementHtml('<style>.disabled {color:red; font-weight:bold;}</style>');
        $form->setValues($model->getData());
		
        //$form->setUseContainer(true);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
