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

class Unirgy_DropshipBatch_Block_Adminhtml_Batch extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_blockGroup = 'udbatch';
        $this->_controller = 'adminhtml_batch';
        $this->_headerText = Mage::helper('udbatch')->__('Vendor PO Import/Export Batches');

        if (Mage::helper('udropship')->isModuleActive('ustockpo')) {
            $this->_addButton('add_export_stockpo', array(
                'label'     => $this->__('Create Stock PO Export Batch'),
                'class'     => 'add',
                'onclick'   => "location.href = '{$this->getUrl('*/*/new', array('type'=>'export_stockpo'))}'",
            ), 0);

            $this->_addButton('add_import_stockpo', array(
                'label'     => $this->__('Create Stock PO Import Batch'),
                'class'     => 'add',
                'onclick'   => "location.href = '{$this->getUrl('*/*/new', array('type'=>'import_stockpo'))}'",
            ), 0);
        }

        $this->_addButton('add_export', array(
            'label'     => $this->__('Create Order Export Batch'),
            'class'     => 'add',
            'onclick'   => "location.href = '{$this->getUrl('*/*/new', array('type'=>'export_orders'))}'",
        ), 0);

        $this->_addButton('add_import', array(
            'label'     => $this->__('Create Tracking Import Batch'),
            'class'     => 'add',
            'onclick'   => "location.href = '{$this->getUrl('*/*/new', array('type'=>'import_orders'))}'",
        ), 0);

        $this->_addButton('add_invimport', array(
            'label'     => $this->__('Create Inventory Import Batch'),
            'class'     => 'add',
            'onclick'   => "location.href = '{$this->getUrl('*/*/new', array('type'=>'import_inventory'))}'",
        ), 0);

        parent::__construct();

        $this->_removeButton('add');
    }

}
