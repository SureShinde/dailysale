<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Preview extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Override for buttons on the form
     * @see parent
     */
    public function __construct()
    {
        $this->_controller = 'recommendations';
        $this->_blockGroup = 'bronto_product';
        parent::__construct();
        $this->removeButton('save');
        $this->removeButton('reset');
        $this->removeButton('delete');

        $this->setTemplate('bronto/product/recommendation/preview.phtml');
    }

    /**
     * Override for the preview functionality
     *
     * @return string
     */
    public function getHeaderText()
    {
        return $this->__('Preview Recommendation');
    }

    /**
     * Override for the preview functionality
     *
     * @return string
     */
    public function getHeaderCssClass()
    {
        return 'icon-head head-products';
    }

    /**
     * Override for the preview form
     *
     * @see parent
     */
    public function _prepareLayout()
    {
        $storeSwitcher = $this->getLayout()->createBlock("adminhtml/store_switcher")
            ->setUseConfirm(false)
            ->setSwitchUrl($this->getUrl('*/*/*', array('store' => null, '_current' => true)));
        $storeSwitcher->hasDefaultOption(false);
        return $this
          ->setChild('form', $this->getLayout()->createBlock("{$this->_blockGroup}/adminhtml_system_recommendation_preview_form"))
          ->setChild('selected_products', $this->getLayout()->createBlock("{$this->_blockGroup}/adminhtml_system_recommendation_selected_js"))
          ->setChild('store_switcher', $storeSwitcher);
    }

    /**
     * The store switcher to change the pulled messages
     *
     * @return string
     */
    public function getStoreSwitcherHtml()
    {
        return $this->getChild('store_switcher')->toHtml();
    }

    /**
     * Is the Magento installation single store only?
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return Mage::app()->isSingleStoreMode();
    }

    /**
     * Provides the selected products js
     * @return string
     */
    public function getSelectedProductsJs()
    {
        return $this->getChild('selected_products')->toHtml();
    }

    /**
     * Gets the AJAX url for updating the recommended content
     * @return string
     */
    public function getUpdatePreviewUrl()
    {
        return $this->getUrl('*/*/previewGrid', array('store' => $this->getRequest()->getParam('store', 1)));
    }

    /**
     * Gets the AJAX url for Bronto message to send to a test contact
     * @return string
     */
    public function getMessageDialogUrl()
    {
        return $this->getUrl('*/*/messages', array('store' => $this->getRequest()->getParam('store', 1)));
    }

    /**
     * Gets the AJAX url for sending the Bronto message to the test contact
     * @return string
     */
    public function getSendMessageUrl()
    {
        return $this->getUrl('*/*/sendMessage', array('store' => $this->getRequest()->getParam('store', 1)));
    }

    /**
     * Gets the correct url for going backward
     * @return string
     */
    public function getBackUrl()
    {
        $params = array();
        if ($this->getRequest()->getParam('entity_id')) {
            $params['id'] = $this->getRequest()->getParam('entity_id');
        }
        return $this->getUrl('*/*/' . $this->getRequest()->getParam('ret', 'index'), $params);
    }
}
