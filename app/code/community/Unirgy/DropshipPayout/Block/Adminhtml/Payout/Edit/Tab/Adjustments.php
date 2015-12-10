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
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipPayout_Block_Adminhtml_Payout_Edit_Tab_Adjustments extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('udpayout_payout_adjustment');
        $this->setDefaultSort('id');
        $this->setUseAjax(true);
    }

    public function getPayout()
    {
        $payout = Mage::registry('payout_data');
        if (!$payout) {
            $payout = Mage::getModel('udpayout/payout')->load($this->getPayoutId());
            Mage::register('payout_data', $payout);
        }
        return $payout;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('udpayout/payout_adjustment')->getCollection()
            ->addFieldToFilter('payout_id', $this->getPayout()->getId());
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'    => Mage::helper('udpayout')->__('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'id'
        ));
        $this->addColumn('adjustment_id', array(
            'header'    => Mage::helper('udpayout')->__('Adjustment ID'),
            'sortable'  => true,
        	'width'     => '300',
            'index'     => 'adjustment_id'
        ));
        $this->addColumn('po_id', array(
            'header'    => Mage::helper('udpayout')->__('Po ID'),
            'sortable'  => true,
        	'width'     => '150',
            'index'     => 'po_id'
        ));
        $this->addColumn('po_type', array(
            'header'    => Mage::helper('udropship')->__('PO Type'),
            'sortable'  => true,
        	'width'     => '100',
            'index'     => 'po_type'
        ));
        $this->addColumn('amount', array(
            'header' => Mage::helper('udpayout')->__('Amount'),
            'index' => 'amount',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => Mage::getStoreConfig('currency/options/base'),
        ));
        $this->addColumn('username', array(
            'header'    => Mage::helper('udropship')->__('Username'),
            'sortable'  => true,
        	'width'     => '150',
            'index'     => 'username'
        ));
        $this->addColumn('comment', array(
            'header'    => Mage::helper('udpayout')->__('Comment'),
            'index'     => 'comment'
        ));
        $this->addColumn('created_at', array(
            'header'    => Mage::helper('udropship')->__('Created At'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'width'     => 150,
        ));
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/adjustmentGrid', array('_current'=>true));
    }
}
