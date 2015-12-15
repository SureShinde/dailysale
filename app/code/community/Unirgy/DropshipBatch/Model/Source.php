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
                'export_orders' => Mage::helper('udropship')->__('Export Orders'),
                'import_orders' => Mage::helper('udropship')->__('Import Orders'),
            	'import_inventory' => Mage::helper('udropship')->__('Import Inventory'),
            );
            if (Mage::helper('udropship')->isModuleActive('ustockpo')) {
                $options['export_stockpo'] = Mage::helper('udropship')->__('Export Stock POs');
                $options['import_stockpo'] = Mage::helper('udropship')->__('Import Stock POs');
            }
            break;

        case 'batch_imported_file_action':
            $options = array(
                '' => Mage::helper('udropship')->__('No Action'),
                'delete' => Mage::helper('udropship')->__('Delete'),
            	'rename' => Mage::helper('udropship')->__('Rename'),
                'move' => Mage::helper('udropship')->__('Move'),
                'rename_move' => Mage::helper('udropship')->__('Rename+Move'),
            );
            break;

        case 'batch_export_orders_adapter':
        case 'batch_adapter':
            $options = array();
            foreach (Mage::getConfig()->getNode('global/udropship/batch_adapters')->children() as $code=>$node) {
                $options[$code] = Mage::helper('udropship')->__((string)$node->label);
            }
            break;

        case 'batch_import_orders_adapter':
            $options = array();
            foreach (Mage::getConfig()->getNode('global/udropship/batch_adapters_import_orders')->children() as $code=>$node) {
                $options[$code] = Mage::helper('udropship')->__((string)$node->label);
            }
            break;
            
        case 'batch_import_inventory_adapter':
            $options = array();
            foreach (Mage::getConfig()->getNode('global/udropship/batch_adapters_import_inventory')->children() as $code=>$node) {
                $options[$code] = Mage::helper('udropship')->__((string)$node->label);
            }
            break;

        case 'batch_import_inventory_reindex':
            $options = array(
                'realtime' => Mage::helper('udropship')->__('Realtime'),
                'full' => Mage::helper('udropship')->__('Full'),
                'manual' => Mage::helper('udropship')->__('Manual'),
            );
            break;

        case 'batch_status':
            $options = array(
                'pending' => Mage::helper('udropship')->__('Pending'),
                'scheduled' => Mage::helper('udropship')->__('Scheduled'),
                'missed' => Mage::helper('udropship')->__('Missed'),
                'processing' => Mage::helper('udropship')->__('Processing'),
                'exporting' => Mage::helper('udropship')->__('Exporting'),
                'importing' => Mage::helper('udropship')->__('Importing'),
                'empty' => Mage::helper('udropship')->__('Empty'),
                'success' => Mage::helper('udropship')->__('Success'),
                'partial' => Mage::helper('udropship')->__('Partial'),
                'error' => Mage::helper('udropship')->__('Error'),
                'canceled' => Mage::helper('udropship')->__('Canceled'),
            );
            break;

        case 'dist_status':
            $options = array(
                'pending' => Mage::helper('udropship')->__('Pending'),
                'processing' => Mage::helper('udropship')->__('Processing'),
                'exporting' => Mage::helper('udropship')->__('Exporting'),
                'importing' => Mage::helper('udropship')->__('Importing'),
                'success' => Mage::helper('udropship')->__('Success'),
                'empty' => Mage::helper('udropship')->__('Empty'),
                'error' => Mage::helper('udropship')->__('Error'),
                'canceled' => Mage::helper('udropship')->__('Canceled'),
            );
            break;

        case 'po_batch_status':
            $options = array(
                '' => Mage::helper('udropship')->__('New'),
                'pending' => Mage::helper('udropship')->__('Pending'),
                'exported' => Mage::helper('udropship')->__('Exported'),
            );
            break;

        case 'batch_export_inventory_method':
            $options = array(
                '' => Mage::helper('udropship')->__('* No export'),
                'manual' => Mage::helper('udropship')->__('Manual only'),
                'auto' => Mage::helper('udropship')->__('Auto Scheduled'),
            );
            break;
        case 'batch_export_orders_method':
            $options = array(
                '' => Mage::helper('udropship')->__('* No export'),
                'manual' => Mage::helper('udropship')->__('Manual only'),
                'auto' => Mage::helper('udropship')->__('Auto Scheduled'),
                'instant' => Mage::helper('udropship')->__('Instant'),
                'status_instant' => Mage::helper('udropship')->__('Instant by status'),
            );
            break;


        case 'batch_import_inventory_method':
        case 'batch_import_orders_method':
            $options = array(
                '' => Mage::helper('udropship')->__('* No import'),
                'manual' => Mage::helper('udropship')->__('Manual only'),
                'auto' => Mage::helper('udropship')->__('Auto Scheduled'),
            );
            break;

        case 'vendors_export_orders':
            $options = $this->getEnabledVendors('export_orders');
            break;

        case 'vendors_import_orders':
            //$options = $this->getEnabledVendors('import_orders');
            $options = Mage::getSingleton('udropship/source')->getVendors(true);
            $options[0] = Mage::helper('udropship')->__("* All Vendors *");
            break;
            
        case 'vendors_import_inventory':
            $options = $this->getEnabledVendors('import_inventory');
            break;

        case 'export_orders_destination':
            $options = array(
                '' => Mage::helper('udropship')->__("Vendor's Default locations"),
                'custom'
            );
            break;

        case 'use_custom_template':
            $options = array(
                '' => Mage::helper('udropship')->__("* Use vendor default"),
            );
            $importTpls = Mage::helper('udbatch')->getManualImportTemplateTitles();
            foreach ($importTpls as $_imtpl) {
                $options[$_imtpl] = $_imtpl;
            }
            break;

        case 'use_custom_export_template':
        case 'udropship/batch/statement_export_template':
            $options = array(
                '' => Mage::helper('udropship')->__("* Use vendor default"),
            );
            $exportTpls = Mage::helper('udbatch')->getManualExportTemplateTitles();
            foreach ($exportTpls as $_extpl) {
                $options[$_extpl] = $_extpl;
            }
            break;

        case 'udropship/batch/invimport_allow_new_association':
            $options = array(
                1 => Mage::helper('udropship')->__('Yes (manual only)'),
                2 => Mage::helper('udropship')->__('Yes (manual and scheduled)'),
                0 => Mage::helper('udropship')->__('No'),
            );
            break;

        case 'udropship/batch/invimport_vsku_multipid':
            $options = array(
                self::INVIMPORT_VSKU_MULTIPID_FIRST  => Mage::helper('udropship')->__('Update only first product'),
                self::INVIMPORT_VSKU_MULTIPID_ALL    => Mage::helper('udropship')->__('Update all products'),
                self::INVIMPORT_VSKU_MULTIPID_REPORT => Mage::helper('udropship')->__('Skip and report error'),
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
            Mage::throwException(Mage::helper('udropship')->__('Invalid request for source options: '.$this->getPath()));
        }

        if ($selector) {
            $options = array(''=>Mage::helper('udropship')->__('* Please select')) + $options;
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