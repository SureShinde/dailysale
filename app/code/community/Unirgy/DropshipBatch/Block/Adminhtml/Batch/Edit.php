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

class Unirgy_DropshipBatch_Block_Adminhtml_Batch_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'udbatch';
        $this->_controller = 'adminhtml_batch';

        if ($this->getRequest()->getParam($this->_objectId)) {
            $this->_removeButton('save');
            $this->_updateButton('delete', 'label', Mage::helper('udropship')->__('Delete Batch'));
            $model = Mage::getModel('udbatch/batch')
                ->load($this->getRequest()->getParam($this->_objectId));
            Mage::register('batch_data', $model);
        } else {
            $this->_updateButton('save', 'label', Mage::helper('udropship')->__('Create Batch(es)'));
        }
    }

    public function getHeaderText()
    {
        $type = $this->getRequest()->getParam('type');
        if (Mage::registry('batch_data') && Mage::registry('batch_data')->getId() ) {
        	$title = '';
        	switch (Mage::registry('batch_data')->getBatchType()) {
        		case 'export_orders':
        			$title = "View Export Orders Batch '%s'";
        			break;
        		case 'import_orders':
        			$title = "View Import Orders Batch '%s'";
        			break;
                case 'export_stockpo':
        			$title = "View Export Stock PO Batch '%s'";
        			break;
        		case 'import_stockpo':
        			$title = "View Import Stock PO Batch '%s'";
        			break;
        		case 'import_inventory':
        			$title = "View Import Inventory Batch '%s'";
        			break;
        	}
            return Mage::helper('udropship')->__(
            	$title, $this->htmlEscape(Mage::registry('batch_data')->getBatchId())
            );
        } else {
        	$title = '';
        	switch ($type) {
        		case 'export_orders':
        			$title = "Export Orders Batch";
        			break;
        		case 'import_orders':
        			$title = "Import Orders Batch";
        			break;
                case 'export_stockpo':
        			$title = "Export Stock PO Batch";
        			break;
        		case 'import_stockpo':
        			$title = "Import Stock PO Batch";
        			break;
        		case 'import_inventory':
        			$title = "Import Inventory Batch";
        			break;
        	}
            return Mage::helper('udropship')->__($title);
        }
    }
}
