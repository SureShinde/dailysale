<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipMicrosite_Block_Adminhtml_Registration_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('registrationGrid');
        $this->setDefaultSort('reg_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('reg_filter');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('umicrosite/registration')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $hlp = Mage::helper('umicrosite');
        $this->addColumn('reg_id', array(
            'header'    => Mage::helper('udropship')->__('Registration ID'),
            'align'     => 'right',
            'width'     => '50px',
            'index'     => 'reg_id',
            'type'      => 'number',
        ));

        $this->addColumn('vendor_name', array(
            'header'    => Mage::helper('udropship')->__('Vendor Name'),
            'index'     => 'vendor_name',
        ));

        $this->addColumn('email', array(
            'header'    => Mage::helper('udropship')->__('Email'),
            'index'     => 'email',
        ));

        $this->addColumn('carrier_code', array(
            'header'    => Mage::helper('udropship')->__('Used Carrier'),
            'index'     => 'carrier_code',
            'type'      => 'options',
            'options'   => Mage::getSingleton('udropship/source')->setPath('carriers')->toOptionHash(),
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('udropship')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('udropship')->__('XML'));
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('reg_id' => $row->getId()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('vendor');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'=> Mage::helper('udropship')->__('Delete'),
             'url'  => $this->getUrl('*/*/massDelete'),
             'confirm' => Mage::helper('udropship')->__('Are you sure?')
        ));

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
