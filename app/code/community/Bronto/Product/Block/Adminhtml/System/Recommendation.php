<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected $_blockGroup = 'bronto_product';
    protected $_controller = 'adminhtml_system_recommendation';
    protected $_addButtonLabel = 'Add New Recommendation';

    /**
     * Override for previewing the recommendation with API tags and emails
     *
     * @see parent
     */
    public function __construct()
    {
        $this->addButton('preview_button', array(
            'label' => $this->__('Preview Recommendation'),
            'onclick' => "setLocation('{$this->getPreviewUrl()}')",
            'class' => 'go',
        ));
        $this->addButton('add_tag_button', array(
            'label' => $this->__('Add Content Tag Recommendation'),
            'onclick' => "setLocation('{$this->getNewContentTagUrl()}')",
            'class' => 'add tag-add',
        ));
        parent::__construct();
        $this->setTemplate('bronto/product/recommendation/grid.phtml');
    }

    /**
     * Adding this link to route back to config page
     *
     * @return string
     */
    public function getConfigLink()
    {
        $url = Mage::helper('bronto_product')->getScopeUrl('*/system_config/edit', array('section' => 'bronto_product'));
        return '<strong>System &rsaquo; Configuration &raquo; Bronto &rsaquo; <a href="' . $url . '">Product Recommendations</a></strong>';
    }

    /**
     * Override for the actual header used
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper($this->_blockGroup)
            ->__('Bronto Product Recommendations');
    }

    /**
     * Override for the icon in the header
     *
     * @return string
     */
    public function getHeaderCssClass()
    {
        return 'icon-head head-products';
    }

    /**
     * Returns the preview url
     *
     * @return string
     */
    public function getPreviewUrl()
    {
        return $this->getUrl('*/*/preview', array('ret' => 'index', '_current' => true));
    }

    /**
     * Returns the content tag URL
     *
     * @return string
     */
    public function getNewContentTagUrl()
    {
        return $this->getUrl('*/*/new', array('ret' => 'index', 'type' => 'content_tag', '_current' => true));
    }
}
