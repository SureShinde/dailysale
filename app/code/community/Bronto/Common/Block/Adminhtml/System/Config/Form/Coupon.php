<?php

class Bronto_Common_Block_Adminhtml_System_Config_Form_Coupon extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * @see parent
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $exampleCode = 'ABC123';
        $store = Mage::app()->getStore();
        $helper = Mage::helper('bronto_common/coupon');
        $comment = $element->getComment();
        $comment = str_replace('{baseUrl}', $store->getUrl('/'), $comment);
        $comment = str_replace('{code}', $helper->getCouponParam(), $comment);
        $comment = str_replace('{example}', $exampleCode, $comment);
        $comment = str_replace('{error}', $helper->getErrorCodeParam(), $comment);
        $element->setComment($comment);
        return parent::render($element);
    }
}
