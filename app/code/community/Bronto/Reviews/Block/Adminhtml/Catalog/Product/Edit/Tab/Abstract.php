<?php

abstract class Bronto_Reviews_Block_Adminhtml_Catalog_Product_Edit_Tab_Abstract
    extends Mage_Adminhtml_Block_Abstract
    implements Mage_Adminhtml_Block_Widget_Tab_Interface, Bronto_Reviews_Block_Adminhtml_Reviews_Typer
{
    protected $_helper;

    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_helper = Mage::helper('bronto_reviews');
        $this->setId("post_{$this->getPostType()}_tab");
    }

    /**
     * Gets the form for a post purchase type
     *
     * @return string
     */
    public function getTabUrl()
    {
        $product = Mage::registry('product');
        return $this->getUrl('*/postpurchase/form', array(
            'type' => $this->getPostType(),
            'product_id' => $product->getId(),
            'store' => $this->getRequest()->getParam('store', 0),
            '_current' => true
        ));
    }

    /**
     * @see parent
     */
    public function getTabClass()
    {
        return 'ajax';
    }

    /**
     * @see parent
     */
    public function getTabLabel()
    {
        return $this->_helper->getPostLabel($this->getPostType());
    }

    /**
     * @see parent
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * @see parent
     */
    public function canShowTab()
    {
        return $this->_helper->isPostEnabled($this->getPostType());
    }

    /**
     * @see parent
     */
    public function isHidden()
    {
        return false;
    }
}
