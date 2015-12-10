<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Email_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);
        $this->setId('logGrid');
        $this->setIdFieldName('log_id');
        $this->setDefaultSort('sent_at', 'desc');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare the collection
     *
     * @return this
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('bronto_email/log')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare the Columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('log_id', array(
            'header' => Mage::helper('bronto_email')->__('ID'),
            'align'  => 'left',
            'index'  => 'log_id',
            'type'   => 'number',
            'filter' => false,
        ));

        $this->addColumn('message_name', array(
            'header' => Mage::helper('bronto_email')->__('Message Name'),
            'align'  => 'left',
            'index'  => 'message_name',
            'filter' => false,
        ));

        $this->addColumn('sent_at', array(
            'header' => Mage::helper('bronto_email')->__('Sent At'),
            'align'  => 'left',
            'index'  => 'sent_at',
            'type'   => 'datetime'
        ));

        $this->addColumn('customer_email', array(
            'header' => Mage::helper('customer')->__('Email'),
            'index'  => 'customer_email',
            //            'renderer' => 'bronto_email/adminhtml_system_email_log_grid_renderer_customer',
        ));

        $this->addColumn('success', array(
            'header'   => Mage::helper('bronto_email')->__('Success'),
            'align'    => 'left',
            'index'    => 'success',
            'sortable' => false,
            'type'     => 'options',
            'options'  => array(
                0 => 'No',
                1 => 'Yes',
            ),
        ));

        $this->addColumn('error', array(
            'header'   => Mage::helper('bronto_email')->__('Error'),
            'align'    => 'left',
            'index'    => 'error',
            'sortable' => false,
            'filter'   => false,
        ));

        $this->addColumn('data', array(
            'header'   => Mage::helper('bronto_email')->__('Fields'),
            'align'    => 'left',
            'index'    => 'data',
            'renderer' => 'bronto_email/adminhtml_system_email_log_grid_renderer_fields',
            'sortable' => false,
            'filter'   => false,
        ));

        return parent::_prepareColumns();
    }

    /**
     * Return row url for js event handlers
     *
     * @param Bronto_Email_Model_Log|Varien_Object
     *
     * @return string
     */
    public function getRowUrl($log)
    {
        return null;
    }
}
