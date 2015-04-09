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

class Unirgy_DropshipBatch_Block_Adminhtml_Batch_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('batch_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('udbatch')->__('Manage Batches'));
    }

    protected function _beforeToHtml()
    {
        $id = Mage::app()->getRequest()->getParam('id', 0);

        if ($id) {
            $batch = Mage::registry('batch_data');
            $this->addTab('form_section', array(
                'label'     => Mage::helper('udropship')->__('Batch Information'),
                'title'     => Mage::helper('udropship')->__('Batch Information'),
                'content'   => $this->getLayout()->createBlock('udbatch/adminhtml_batch_edit_tab_form')
                    ->setVendorId($id)
                    ->toHtml(),
            ));

            $export = in_array($batch->getBatchType(), array('export_orders', 'export_stockpo', 'export_inventory'));
            $this->addTab('dist_section', array(
                'label'     => Mage::helper('udbatch')->__($export ? 'Destinations' : 'Sources'),
                'title'     => Mage::helper('udbatch')->__($export ? 'Destinations' : 'Sources'),
                'content'   => $this->getLayout()->createBlock('udbatch/adminhtml_batch_edit_tab_dist', 'udbatch.dist.grid')
                    ->setVendorId($id)
                    ->toHtml(),
            ));

            if ($export) {
                $block = $this->getLayout()->createBlock('udbatch/adminhtml_batch_edit_tab_export_rows', 'udbatch.rows.grid');
            } else {
                $block = $this->getLayout()->createBlock('udbatch/adminhtml_batch_edit_tab_import_rows', 'udbatch.rows.grid');
            }
            $this->addTab('rows_section', array(
                'label'     => Mage::helper('udbatch')->__('Data Rows'),
                'title'     => Mage::helper('udbatch')->__('Data Rows'),
                'content'   => $block->setVendorId($id)->toHtml(),
            ));
        } else {
            if ($this->getRequest()->getParam('type')=='export_orders') {
                $this->addTab('export_section', array(
                    'label'     => Mage::helper('udropship')->__('Export Orders'),
                    'title'     => Mage::helper('udropship')->__('Export Orders'),
                    'content'   => $this->getLayout()->createBlock('udbatch/adminhtml_batch_edit_tab_export')->toHtml(),
                ));
            } elseif ($this->getRequest()->getParam('type')=='import_orders') {
                $this->addTab('import_section', array(
                    'label'     => Mage::helper('udropship')->__('Import Orders'),
                    'title'     => Mage::helper('udropship')->__('Import Orders'),
                    'content'   => $this->getLayout()->createBlock('udbatch/adminhtml_batch_edit_tab_import')->toHtml(),
                ));
            } elseif ($this->getRequest()->getParam('type')=='export_stockpo') {
                $this->addTab('export_section', array(
                    'label'     => Mage::helper('udropship')->__('Export Stock PO'),
                    'title'     => Mage::helper('udropship')->__('Export Stock PO'),
                    'content'   => $this->getLayout()->createBlock('udbatch/adminhtml_batch_edit_tab_export')->toHtml(),
                ));
            } elseif ($this->getRequest()->getParam('type')=='import_stockpo') {
                $this->addTab('import_section', array(
                    'label'     => Mage::helper('udropship')->__('Import Stock PO'),
                    'title'     => Mage::helper('udropship')->__('Import Stock PO'),
                    'content'   => $this->getLayout()->createBlock('udbatch/adminhtml_batch_edit_tab_import')->toHtml(),
                ));
            } elseif ($this->getRequest()->getParam('type')=='import_inventory') {
                $this->addTab('import_section', array(
                    'label'     => Mage::helper('udropship')->__('Import Inventory'),
                    'title'     => Mage::helper('udropship')->__('Import Inventory'),
                    'content'   => $this->getLayout()->createBlock('udbatch/adminhtml_batch_edit_tab_invimport')->toHtml(),
                ));
            }
        }

        return parent::_beforeToHtml();
    }
}