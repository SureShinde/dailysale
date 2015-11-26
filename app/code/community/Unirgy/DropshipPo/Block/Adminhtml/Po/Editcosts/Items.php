<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
 
class Unirgy_DropshipPo_Block_Adminhtml_Po_Editcosts_Items extends Mage_Adminhtml_Block_Sales_Items_Abstract
{
    public function getPo()
    {
        return Mage::registry('current_udpo');
    }
    
    public function getOrder()
    {
        return Mage::registry('current_order');
    }
    
    protected function _beforeToHtml()
    {
        $this->setChild(
            'submit_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                'label'     => Mage::helper('udropship')->__('Save Costs Update'),
                'class'     => 'save submit-button',
                'onclick'   => 'disableElements(\'submit-button\');$(\'edit_form\').submit()',
            ))
        );

        return parent::_beforeToHtml();
    }
    
    public function getCommentText()
    {
        return Mage::getSingleton('adminhtml/session')->getCommentText(true);
    }
}