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
 * @package    Unirgy_DropshipBatch
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

/**
* Currently not in use
*/
class Unirgy_DropshipBatch_Model_Source extends Unirgy_Dropship_Model_Source_Abstract
{
    const NEW_ASSOCIATION_NO = 0;
    const NEW_ASSOCIATION_YES_MANUAL = 1;
    const NEW_ASSOCIATION_YES = 2;

    const INVIMPORT_VSKU_MULTIPID_FIRST = 0;
    const INVIMPORT_VSKU_MULTIPID_ALL = 1;
    const INVIMPORT_VSKU_MULTIPID_REPORT = 2;

    protected $_batchVendors;

    public function toOptionHash($selector=false)
    {
        $hlp = Mage::helper('udropship');
        $hlpb = Mage::helper('udbatch');

        switch ($this->getPath()) {

        case 'batch_type':
            $options = array(
                'export_orders' => $hlpb->__('Export Orders'),
                'import_orders' => $hlpb->__('Import Orders'),
            	'import_inventory' => $hlpb->__('Import Inventory'),
            );
            if (Mage::helper('udropship')->isModuleActive('ustockpo')) {
                $options['export_stockpo'] = $hlpb->__('Export Stock POs');
                $options['import_stockpo'] = $hlpb->__('Import Stock POs');
            }
            break;

        case 'batch_imported_file_action':
            $options = array(
                '' => $hlpb->__('No Action'),
                'delete' => $hlpb->__('Delete'),
            	'rename' => $hlpb->__('Rename'),
                'move' => $hlpb->__('Move'),
                'rename_move' => $hlpb->__('Rename+Move'),
            );
            break;

        case 'batch_export_orders_adapter':
        case 'batch_adapter':
            $options = array();
            foreach (Mage::getConfig()->getNode('global/udropship/batch_adapters')->children() as $code=>$node) {
                $options[$code] = $hlpb->__((string)$node->label);
            }
            break;

        case 'batch_import_orders_adapter':
            $options = array();
            foreach (Mage::getConfig()->getNode('global/udropship/batch_adapters_import_orders')->children() as $code=>$node) {
                $options[$code] = $hlpb->__((string)$node->label);
            }
            break;
            
        case 'batch_import_inventory_adapter':
            $options = array();
            foreach (Mage::getConfig()->getNode('global/udropship/batch_adapters_import_inventory')->children() as $code=>$node) {
                $options[$code] = $hlpb->__((string)$node->label);
            }
            break;

        case 'batch_import_inventory_reindex':
            $options = array(
                'realtime' => $hlpb->__('Realtime'),
                'full' => $hlpb->__('Full'),
                'manual' => $hlpb->__('Manual'),
            );
            break;

        case 'batch_status':
            $options = array(
                'pending' => $hlpb->__('Pending'),
                'scheduled' => $hlpb->__('Scheduled'),
                'missed' => $hlpb->__('Missed'),
                'processing' => $hlpb->__('Processing'),
                'exporting' => $hlpb->__('Exporting'),
                'importing' => $hlpb->__('Importing'),
                'empty' => $hlpb->__('Empty'),
                'success' => $hlpb->__('Success'),
                'partial' => $hlpb->__('Partial'),
                'error' => $hlpb->__('Error'),
                'canceled' => $hlpb->__('Canceled'),
            );
            break;

        case 'dist_status':
            $options = array(
                'pending' => $hlpb->__('Pending'),
                'processing' => $hlpb->__('Processing'),
                'exporting' => $hlpb->__('Exporting'),
                'importing' => $hlpb->__('Importing'),
                'success' => $hlpb->__('Success'),
                'empty' => $hlpb->__('Empty'),
                'error' => $hlpb->__('Error'),
                'canceled' => $hlpb->__('Canceled'),
            );
            break;

        case 'po_batch_status':
            $options = array(
                '' => $hlpb->__('New'),
                'pending' => $hlpb->__('Pending'),
                'exported' => $hlpb->__('Exported'),
            );
            break;

        case 'batch_export_inventory_method':
            $options = array(
                '' => $hlpb->__('* No export'),
                'manual' => $hlpb->__('Manual only'),
                'auto' => $hlpb->__('Auto Scheduled'),
            );
            break;
        case 'batch_export_orders_method':
            $options = array(
                '' => $hlpb->__('* No export'),
                'manual' => $hlpb->__('Manual only'),
                'auto' => $hlpb->__('Auto Scheduled'),
                'instant' => $hlpb->__('Instant'),
                'status_instant' => $hlpb->__('Instant by status'),
            );
            break;


        case 'batch_import_inventory_method':
        case 'batch_import_orders_method':
            $options = array(
                '' => $hlpb->__('* No import'),
                'manual' => $hlpb->__('Manual only'),
                'auto' => $hlpb->__('Auto Scheduled'),
            );
            break;

        case 'vendors_export_orders':
            $options = $this->getEnabledVendors('export_orders');
            break;

        case 'vendors_import_orders':
            $options = $this->getEnabledVendors('import_orders');
            break;
            
        case 'vendors_import_inventory':
            $options = $this->getEnabledVendors('import_inventory');
            break;

        case 'export_orders_destination':
            $options = array(
                '' => $hlp->__("Vendor's Default locations"),
                'custom'
            );
            break;

        case 'use_custom_template':
            $options = array(
                '' => $hlp->__("* Use vendor default"),
            );
            $importTpls = Mage::helper('udbatch')->getManualImportTemplateTitles();
            foreach ($importTpls as $_imtpl) {
                $options[$_imtpl] = $_imtpl;
            }
            break;

        case 'use_custom_export_template':
        case 'udropship/batch/statement_export_template':
            $options = array(
                '' => $hlp->__("* Use vendor default"),
            );
            $exportTpls = Mage::helper('udbatch')->getManualExportTemplateTitles();
            foreach ($exportTpls as $_extpl) {
                $options[$_extpl] = $_extpl;
            }
            break;

        case 'udropship/batch/invimport_allow_new_association':
            $options = array(
                1 => $hlp->__('Yes (manual only)'),
                2 => $hlp->__('Yes (manual and scheduled)'),
                0 => $hlp->__('No'),
            );
            break;

        case 'udropship/batch/invimport_vsku_multipid':
            $options = array(
                self::INVIMPORT_VSKU_MULTIPID_FIRST  => $hlp->__('Update only first product'),
                self::INVIMPORT_VSKU_MULTIPID_ALL    => $hlp->__('Update all products'),
                self::INVIMPORT_VSKU_MULTIPID_REPORT => $hlp->__('Skip and report error'),
            );
            break;

        case 'stock_status':
            $options = array();
            $cissOptions = Mage::getSingleton('cataloginventory/source_stock')->toOptionArray();
            foreach ($cissOptions as $_cissOpt) {
                $options[$_cissOpt['value']] = $_cissOpt['label'];
            }
            break;

        default:
            Mage::throwException($hlp->__('Invalid request for source options: '.$this->getPath()));
        }

        if ($selector) {
            $options = array(''=>$hlp->__('* Please select')) + $options;
        }

        return $options;
    }

    public function getEnabledVendors($type)
    {
        if (empty($this->_batchVendors[$type])) {
            $this->_batchVendors[$type] = array();
            $vendors = Mage::getModel('udropship/vendor')->getCollection()
                ->addStatusFilter('A')
                ->setOrder('vendor_name', 'asc');
            $vendors->getSelect()->where("batch_{$type}_method<>''");
            foreach ($vendors as $v) {
                $this->_batchVendors[$type][$v->getId()] = $v->getVendorName();
            }
        }
        return $this->_batchVendors[$type];
    }

}