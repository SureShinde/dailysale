<?php

class Bronto_Reviews_Block_Adminhtml_Reviews_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected $_helper;

    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_helper = Mage::helper('bronto_reviews');
        $this->setId('post_delivery_grid');
        $this->setIdFieldName('log_id');
        $this->setDefaultSort('delivery_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepares the post purchase delivery grid
     *
     * @return Bronto_Reviews_Block_Adminhtml_Reviews_Grid
     */
    protected function _prepareCollection()
    {
        $this->_beforePrepareCollection();
        return parent::_prepareCollection();
    }

    /**
     * Allow convenient override for the collection
     *
     * @return Bronto_Reviews_Block_Adminhtml_Reviews_Grid
     */
    protected function _beforePrepareCollection()
    {
        $collection = Mage::getModel('bronto_reviews/log')->getCollection();
        $this->setCollection($collection);
        return $this;
    }

    /**
     * Prepares the mass action block
     *
     * @return Bronto_Reviews_Block_Adminhtml_Reviews_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('log_id');
        $this->getMassactionBlock()->setFormFieldName('id');

        $this->getMassactionBlock()->addItem('cancel', array(
            'label' => $this->_helper->__('Cancel'),
            'confirm' => $this->_helper->__('Are you sure?'),
            'url' => $this->getUrl('*/postpurchase/cancel')
        ));
        $this->getMassactionBlock()->addItem('delete', array(
            'label' => $this->_helper->__('Purge'),
            'confirm' => $this->_helper->__('Are you sure?'),
            'url' => $this->getUrl('*/postpurchase/delete')
        ));
        return $this;
    }

    /**
     * Prepares the columns of the grid
     *
     * @see parent
     */
    protected function _prepareColumns()
    {
        $yesNoOptions = Mage::getModel('adminhtml/system_config_source_yesno');
        $options = array();
        foreach ($yesNoOptions->toOptionArray() as $option) {
            $options[$option['value']] = $option['label'];
        }

        $this->addColumn('log_id', array(
            'header' => $this->_helper->__('ID'),
            'align' => 'left',
            'index' => 'log_id',
            'type' => 'number',
            'filter' => false
        ));

        $this->addColumn('message_name', array(
            'header' => $this->_helper->__('Message Name'),
            'align' => 'left',
            'index' => 'message_name',
            'type' => 'text',
            'sortable' => false,
            'filter' => false
        ));

        $this->addColumn('order_increment_id', array(
            'header' => $this->_helper->__('Order Increment ID'),
            'align' => 'left',
            'index' => 'order_increment_id',
            'renderer' => 'bronto_reviews/adminhtml_reviews_grid_renderer_order',
            'type' => 'text',
            'sortable' => false,
            'filter' => false,
        ));

        $this->addColumn('post_name', array(
            'header' => $this->_helper->__('Post Type'),
            'align' => 'left',
            'index' => 'post_name',
            'type' => 'text',
            'sortable' => false,
            'filter' => false
        ));

        $this->addColumn('product_name', array(
            'header' => $this->_helper->__('Product Name'),
            'align' => 'left',
            'index' => 'product_name',
            'renderer' => 'bronto_reviews/adminhtml_reviews_grid_renderer_product',
            'type' => 'text',
            'sortable' => false,
            'filter' => false
        ));

        $this->addColumn('delivery_date', array(
            'header' => $this->_helper->__('Delivery Date'),
            'align' => 'left',
            'index' => 'delivery_date',
            'type' => 'datetime'
        ));

        $this->addColumn('customer_email', array(
            'header' => Mage::helper('customer')->__('Email'),
            'index' => 'customer_email'
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => $this->_helper->__('Store View'),
                'type' => 'store',
                'skipAllStoresLabel' => true,
                'index' => 'store_id',
                'sortable' => false,
                'store_view' => true,
                'width' => '120px'
            ));
        }

        $this->addColumn('cancelled', array(
            'header' => $this->_helper->__('Cancelled'),
            'index' => 'cancelled',
            'type' => 'options',
            'options' => $options,
            'align' => 'left',
            'sortable' => false,
        ));

        $this->addColumn('data', array(
            'header' => Mage::helper('bronto_email')->__('Fields'),
            'align' => 'left',
            'index' => 'fields',
            'renderer' => 'bronto_email/adminhtml_system_email_log_grid_renderer_fields',
            'sortable' => false,
            'filter' => false,
        ));

        $this->addColumn('action', array(
            'header' => $this->_helper->__('Action'),
            'index' => 'log_id',
            'sortable' => false,
            'filter' => false,
            'renderer' => 'bronto_reviews/adminhtml_reviews_grid_renderer_action',
        ));

        return parent::_prepareColumns();
    }
}
