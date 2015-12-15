<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Reminder_Edit_Tab_Customers extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Intialize grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('customerGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('customer_email');
        $this->setDefaultDir('ASC');
        $this->setDefaultFilter(array('grid_is_active' => 1));
    }

    /**
     * Instantiate and prepare collection
     *
     * @return Bronto_Reminder_Block_Adminhtml_Reminder_Edit_Tab_Customers
     */
    protected function _prepareCollection()
    {
        /* @var $collection Bronto_Reminder_Model_Mysql4_Customer_Collection */
        $collection = Mage::getResourceModel('bronto_reminder/customer_collection');
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare columns for grid
     *
     * @return Bronto_Reminder_Block_Adminhtml_Reminder_Edit_Tab_Customers
     */
    protected function _prepareColumns()
    {
        $this->addColumn('grid_entity_id', array(
            'header'   => Mage::helper('bronto_reminder')->__('ID'),
            'align'    => 'center',
            'width'    => 50,
            'index'    => 'customer_id',
            'renderer' => 'bronto_reminder/adminhtml_widget_grid_column_renderer_id'
        ));

        $this->addColumn('grid_email', array(
            'header'   => Mage::helper('bronto_reminder')->__('Email'),
            'type'     => 'text',
            'align'    => 'left',
            'index'    => 'customer_email',
            'renderer' => 'bronto_reminder/adminhtml_widget_grid_column_renderer_email'
        ));

        $this->addColumn('grid_store', array(
            'header' => Mage::helper('bronto_reminder')->__('Store View'),
            'type' => 'store',
            'skipAllStoresLabel' => true,
            'index' => 'store_id',
            'sortable' => false,
            'store_view' => true,
        ));

        $this->addColumn('grid_associated_at', array(
            'header'  => Mage::helper('bronto_reminder')->__('Matched At'),
            'align'   => 'left',
            'width'   => 150,
            'type'    => 'datetime',
            'default' => '--',
            'index'   => 'associated_at'
        ));

        $this->addColumn('grid_is_active', array(
            'header'  => Mage::helper('bronto_reminder')->__('Thread Active'),
            'align'   => 'left',
            'type'    => 'options',
            'index'   => 'is_active',
            'options' => array(
                '0' => Mage::helper('bronto_reminder')->__('No'),
                '1' => Mage::helper('bronto_reminder')->__('Yes')
            )
        ));

        if (class_exists('Mage_SalesRule_Model_Coupon', false)) {

            $this->addColumn('grid_code', array(
                'header'  => Mage::helper('bronto_reminder')->__('Coupon'),
                'align'   => 'left',
                'default' => Mage::helper('bronto_reminder')->__('N/A'),
                'index'   => 'code'
            ));

            $this->addColumn('grid_usage_limit', array(
                'header'  => Mage::helper('bronto_reminder')->__('Coupon Usage Limit'),
                'align'   => 'left',
                'default' => '0',
                'index'   => 'usage_limit'
            ));

            $this->addColumn('grid_usage_per_customer', array(
                'header'  => Mage::helper('bronto_reminder')->__('Coupon Usage per Customer'),
                'align'   => 'left',
                'default' => '0',
                'index'   => 'usage_per_customer'
            ));

        }

        $this->addColumn('grid_emails_sent', array(
            'header'  => Mage::helper('bronto_reminder')->__('Emails Sent'),
            'align'   => 'left',
            'default' => '0',
            'index'   => 'emails_sent'
        ));

        $this->addColumn('grid_emails_failed', array(
            'header' => Mage::helper('bronto_reminder')->__('Emails Failed'),
            'align'  => 'left',
            'index'  => 'emails_failed'
        ));

        $this->addColumn('grid_last_sent', array(
            'header'  => Mage::helper('bronto_reminder')->__('Last Sent At'),
            'align'   => 'left',
            'width'   => 150,
            'type'    => 'datetime',
            'default' => '--',
            'index'   => 'last_sent'
        ));

        return parent::_prepareColumns();
    }
}
