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
 
class Unirgy_DropshipPo_Block_Adminhtml_Po_View extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        $this->_blockGroup  = 'udpo';
        $this->_objectId    = 'udpo_id';
        $this->_controller  = 'adminhtml_po';
        $this->_mode        = 'view';

        parent::__construct();

        $this->_removeButton('reset');
        $this->_removeButton('delete');
        $this->_removeButton('save');

        if ($this->getPo()->getId()) {

            if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/udpo_edit_cost')) {
                $this->_addButton('po_editcosts', array(
                    'label'     => Mage::helper('udpo')->__('Edit Costs'),
                    'onclick'   => 'setLocation(\'' . $this->getEditCostsUrl() . '\')',
                    'class'     => 'go'
                ));
            }

	        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/ship') && $this->getPo()->canCreateShipment()) {
	            $this->_addButton('po_create_shipment', array(
	                'label'     => Mage::helper('udpo')->__('Create Shipment'),
	                'onclick'   => 'setLocation(\'' . $this->getCreateShipmentUrl() . '\')',
	                'class'     => 'go'
	            ));
	        }
        	
            $this->_addButton('print', array(
                'label'     => Mage::helper('sales')->__('Print'),
                'class'     => 'save',
                'onclick'   => 'setLocation(\''.$this->getPrintUrl().'\')'
                )
            );
        }
    }

    public function getEditCostsUrl()
    {
        return $this->getUrl('*/*/editCosts', array('udpo_id'=>$this->getPo()->getId()));
    }
    
	public function getCreateShipmentUrl()
    {
        return $this->getUrl('*/*/newShipment', array('udpo_id'=>$this->getPo()->getId()));
    }

    public function getPo()
    {
        return Mage::registry('current_udpo');
    }

    public function getHeaderText()
    {
        return Mage::helper('udpo')->__('Purchase Order #%1$s | %2$s', $this->getPo()->getIncrementId(), $this->formatDate($this->getPo()->getCreatedAtDate(), 'medium', true));
    }

    public function getBackUrl()
    {
        return $this->getUrl(
            'adminhtml/sales_order/view',
            array(
                'order_id'  => $this->getPo()->getOrderId(),
                'active_tab'=> 'order_udpos'
            ));
    }

    public function getPrintUrl()
    {
        return $this->getUrl('*/po/print', array(
            'udpo_id' => $this->getPo()->getId()
        ));
    }

    public function updateBackButtonUrl($flag)
    {
        if ($flag) {
            return $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/po/') . '\')');
        }
        return $this;
    }
}