<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Reminder_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('reminderGrid');
        $this->setIdFieldName('rule_id');
        $this->setDefaultSort('rule_id', 'asc');
        $this->setDefaultFilter('is_active', '1');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('bronto_reminder/rule')->getCollection();
        $collection->addWebsitesToResult();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('rule_id', array(
            'header' => Mage::helper('bronto_reminder')->__('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'rule_id',
        ));

        $this->addColumn('name', array(
            'header' => Mage::helper('bronto_reminder')->__('Rule Name'),
            'align'  => 'left',
            'index'  => 'name',
        ));

        $this->addColumn('from_date', array(
            'header'  => Mage::helper('bronto_reminder')->__('Active From'),
            'align'   => 'left',
            'width'   => '120px',
            'type'    => 'date',
            'default' => '--',
            'index'   => 'active_from',
        ));

        $this->addColumn('to_date', array(
            'header'  => Mage::helper('bronto_reminder')->__('Active To'),
            'align'   => 'left',
            'width'   => '120px',
            'type'    => 'date',
            'default' => '--',
            'index'   => 'active_to',
        ));

        $this->addColumn('is_active', array(
            'header'  => Mage::helper('bronto_reminder')->__('Status'),
            'align'   => 'left',
            'width'   => '80px',
            'index'   => 'is_active',
            'type'    => 'options',
            'options' => array(
                1 => 'Active',
                0 => 'Inactive',
            ),
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('rule_website', array(
                'header'   => Mage::helper('bronto_reminder')->__('Website'),
                'align'    => 'left',
                'index'    => 'website_ids',
                'type'     => 'options',
                'sortable' => false,
                'options'  => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash(),
                'width'    => 200,
            ));
        }

        return parent::_prepareColumns();
    }

    /**
     * Return url for current row
     *
     * @param Bronto_Reminder_Model_Rule $row
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getRuleId()));
    }
}
