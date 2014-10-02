<?php

class TM_NewsletterBooster_Block_Adminhtml_Gateway_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('newsletterbooster_gateway_grid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');

        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('newsletterbooster/gateway')->getCollection();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
          'header'    => Mage::helper('newsletterbooster')->__('ID'),
          'align'     => 'right',
          'width'     => '50px',
          'index'     => 'id',
          'type'      => 'number'
        ));

        $this->addColumn('name', array(
          'header'    => Mage::helper('newsletterbooster')->__('Title'),
          'align'     => 'left',
          'index'     => 'name',
        ));

        $this->addColumn('status', array(
          'header'    => Mage::helper('newsletterbooster')->__('Status'),
          'align'     => 'left',
          'width'     => '80px',
          'index'     => 'status',
          'type'      => 'options',
          'options'   => array(
                1     => Mage::helper('newsletterbooster')->__('Enabled'),
                0     => Mage::helper('newsletterbooster')->__('Disabled')
            )
        ));

        $this->addColumn('host', array(
          'header'    => Mage::helper('newsletterbooster')->__('Host'),
          'align'     => 'left',
          'index'     => 'host',
        ));

        $this->addColumn('user', array(
          'header'    => Mage::helper('newsletterbooster')->__('User'),
          'align'     => 'left',
          'index'     => 'user',
        ));

        $this->addColumn('port', array(
          'header'    => Mage::helper('newsletterbooster')->__('Port'),
          'align'     => 'left',
          'index'     => 'port',
        ));

        $this->addColumn('secure', array(
          'header'    => Mage::helper('newsletterbooster')->__('Secure'),
          'align'     => 'left',
          'width'     => '80px',
          'index'     => 'secure',
          'type'      => 'options',
          'options'   => array(
                false     => Mage::helper('newsletterbooster')->__('None'),
                'tls'     => Mage::helper('newsletterbooster')->__('SSL/TLS'),
                'ssl'     => Mage::helper('newsletterbooster')->__('STARTTLS')
            )
        ));

        $this->addColumn('action', array(
            'header'    =>  Mage::helper('newsletterbooster')->__('Action'),
            'width'     => '100',
            'type'      => 'action',
            'getter'    => 'getId',
            'actions'   => array(
                array(
                    'caption'   => Mage::helper('newsletterbooster')->__('Edit'),
                    'url'       => array('base'=> '*/*/edit'),
                    'field'     => 'id'
                )
            ),
            'filter'    => false,
            'sortable'  => false,
            'index'     => 'stores',
            'is_system' => true,
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('newsletterbooster')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('newsletterbooster')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('newsletterbooster');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('newsletterbooster')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('newsletterbooster')->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}