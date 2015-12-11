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

class Unirgy_DropshipPo_Block_Adminhtml_Po_Editcosts extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'udpo';
        $this->_objectId = 'order_id';
        $this->_controller = 'adminhtml_po';
        $this->_mode = 'editcosts';

        parent::__construct();

        $this->_removeButton('save');
    }

    public function getPo()
    {
        return Mage::registry('current_udpo');
    }

    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function getHeaderText()
    {
        $header = Mage::helper('udropship')->__('Edit Costs for Purchase Orders #%s', $this->getPo()->getIncrementId());
        return $header;
    }

    public function getBackUrl()
    {
        return $this->getUrl('adminhtml/udpoadmin_order_po/view', array('udpo_id'=>$this->getPo()->getId()));
    }
}