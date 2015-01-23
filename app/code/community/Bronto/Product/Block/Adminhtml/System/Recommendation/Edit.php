<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Overrride for buttons on the form
     * @see parent
     */
    public function __construct()
    {
        $this->_controller = 'recommendations';
        $this->_blockGroup = 'bronto_product';
        parent::__construct();

        $this->addButton('save_and_edit_button', array(
            'label' => $this->__('Save and Continue Edit'),
            'onclick' => "editForm.submit('" . $this->getSaveAndEditUrl() . "')",
            'class' => 'save'
        ), 1);

        if ($this->getRequest()->getParam('id')) {
            $this->addButton('preview_button', array(
                'label' => $this->__('Preview Recommendation'),
                'onclick' => "setLocation('{$this->getPreviewUrl()}')",
                'class' => 'go'
            ));
        }

        // TODO: use this if we're going to be doing something crazy
        $this->setTemplate('bronto/product/recommendation/edit.phtml');
    }

    /**
     * Override for the form header
     *
     * @see parent
     * @return string
     */
    public function getHeaderText()
    {
        $rec = Mage::registry('current_product_recommendation');
        if ($rec->hasEntityId()) {
            return $this->__("Edit Recommendation '%s'", $rec->getName());
        } else {
            return $this->__("New Recommendation");
        }
    }

    /**
     * Override for the icon used in the header
     *
     * @see parent
     * @return string
     */
    public function getHeaderCssClass()
    {
        return 'icon-head head-products';
    }

    /**
     * Override to get the rigt form for the container
     *
     * @see parent
     */
    protected function _prepareLayout()
    {
        return $this
            ->setChild('form', $this->getLayout()->createBlock("{$this->_blockGroup}/adminhtml_system_recommendation_{$this->_mode}_form"))
            ->setChild('selected_products', $this->getLayout()->createBlock("{$this->_blockGroup}/adminhtml_system_recommendation_selected_js"));
    }

    /**
     * Gets the the delete url
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', array('id' => $this->getRequest()->getParam('id')));
    }

    /**
     * Gets the save url
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', array('_current' => true));
    }

    /**
     * Gets the AJAX validation url
     *
     * @return string
     */
    public function getValidationUrl()
    {
      return $this->getUrl('*/*/validate', array('_current' => true));
    }

    /**
     * Provides the selected products js
     *
     * @return string
     */
    public function getSelectedProductsJs()
    {
        return $this->getChild('selected_products')->toHtml();
    }

    /**
     * Gets the save and continue edit url
     *
     * @return string
     */
    public function getSaveAndEditUrl()
    {
        return $this->getUrl("*/*/save", array('_current' => true, 'continue' => true));
    }

    /**
     * URL to post the content tag content for preview
     *
     * @return string
     */
    public function getPreviewContentUrl()
    {
        return $this->getUrl('*/*/content', array('_current' => true));
    }

    /**
     * Provides the preview url for an editing recommendation
     *
     * @return string
     */
    public function getPreviewUrl()
    {
        return $this->getUrl('*/*/preview', array('entity_id' => $this->getRequest()->getParam('id'), 'ret' => 'edit'));
    }
}
