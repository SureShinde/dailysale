<?php

class Bronto_Reviews_Block_Adminhtml_Sales_Order_View_Tab_Post_Purchase
    extends Bronto_Reviews_Block_Adminhtml_Reviews_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected $_singleton;

    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_singleton = Mage::getModel('bronto_reviews/post_purchase');
        $this->setId('post_order_grid');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
    }

    /**
     * Override for order view
     * @see parent
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        unset($this->_columns['order_increment_id']);
        unset($this->_columns['customer_email']);
        unset($this->_columns['store_id']);
        return $this;
    }

    /**
     * @see parent
     */
    protected function _beforePrepareCollection()
    {
        if ($this->getOrder() && $this->getOrder()->getId()) {
            $collection = Mage::getModel('bronto_reviews/log')->getCollection();
            $collection->addFieldToFilter('order_id', array('eq' => $this->getOrder()->getId()));
            $this->setCollection($collection);
            return $this;
        }
        return parent::_beforePrepareCollection();
    }

    /**
     * @see parent
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/postpurchase/order', array('_current' => true));
    }

    /**
     * Gets the order model from the registry
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * @see parent
     */
    public function getTabLabel()
    {
        return $this->_helper->__('Post-Purchase Emails');
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
        if (!$this->getOrder() || !$this->getOrder()->getStoreId()) {
            return false;
        }
        $storeId = $this->getOrder()->getStoreId();
        $enabled = $this->_helper->isEnabled('store', $storeId);
        foreach ($this->_singleton->getSupportedTypes() as $postType) {
            $enabled = $enabled || $this->_helper->isPostEnabled($postType, 'store', $storeId);
        }
        return $enabled;
    }

    /**
     * @see parent
     */
    public function isHidden()
    {
        return false;
    }
}
