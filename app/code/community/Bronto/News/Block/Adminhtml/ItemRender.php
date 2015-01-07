<?php

class Bronto_News_Block_Adminhtml_ItemRender extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{

    /**
     * @see parent
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('bronto/news/item.phtml');
    }

    /**
     * @see parent
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $vars = array('date' => Mage::getModel('core/date'), 'element' => $element);

        return $this->assign($vars)->toHtml();
    }
}
