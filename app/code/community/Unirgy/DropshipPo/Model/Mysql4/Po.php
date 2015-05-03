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
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipPo_Model_Mysql4_Po extends Mage_Sales_Model_Mysql4_Order_Abstract
{
    protected $_eventPrefix = 'udpo_po_resource';
    protected $_grid = true;
    protected $_useIncrementId = true;
    protected $_entityTypeForIncrementId = 'udpo_po';

    protected function _construct()
    {
        $this->_init('udpo/po', 'entity_id');
    }

    protected function _initVirtualGridColumns()
    {
        parent::_initVirtualGridColumns();
        $this->addVirtualGridColumn(
                'shipping_name',
                'sales/order_address',
                array('shipping_address_id' => 'entity_id'),
                'CONCAT(IFNULL({{table}}.firstname, ""), " ", IFNULL({{table}}.lastname, ""))'
            )
            ->addVirtualGridColumn(
                'order_increment_id',
                'sales/order',
                array('order_id' => 'entity_id'),
                'increment_id'
            )
            ->addVirtualGridColumn(
                'order_created_at',
                'sales/order',
                array('order_id' => 'entity_id'),
                'created_at'
            )
            ;

        return $this;
    }
    
    public function hasExternalInvoice($po, $oItemIds)
    {
        return $this->getReadConnection()->fetchOne(
            $this->getReadConnection()->select()
                ->from(array('sii' => $this->getTable('sales/invoice_item')), array())
                ->join(array('si' => $this->getTable('sales/invoice')), 'sii.parent_id=si.entity_id', array())
                ->where('sii.order_item_id in (?)', $oItemIds)
                ->where('si.udpo_id!=?', $po->getId())
                ->columns('count(*)')
        );        
    }
}
