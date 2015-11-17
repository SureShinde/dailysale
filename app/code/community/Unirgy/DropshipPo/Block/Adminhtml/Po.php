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
 
class Unirgy_DropshipPo_Block_Adminhtml_Po extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected $_blockGroup = 'udpo';

    public function __construct()
    {
        $this->_controller = 'adminhtml_po';
        $this->_headerText = Mage::helper('udropship')->__('Purchase Orders');
        parent::__construct();
        $this->_removeButton('add');
    }
}