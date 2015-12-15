<?php

class Unirgy_Rma_Block_Adminhtml_Rma extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected $_blockGroup = 'urma';

    public function __construct()
    {
        $this->_controller = 'adminhtml_rma';
        $this->_headerText = Mage::helper('udropship')->__('uReturns');
        parent::__construct();
        $this->_removeButton('add');
    }
}