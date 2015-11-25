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

class Unirgy_DropshipBatch_Vendor_BatchController extends Unirgy_Dropship_Controller_VendorAbstract
{
	protected function _getSession()
    {
        return Mage::getSingleton('udropship/session');
    }
	public function importStockAction()
    {
        $this->_renderPage(null, 'importstock');
    }
	public function importOrdersAction()
    {
        $this->_renderPage(null, 'importorders');
    }
    public function getVendorShipmentCollection()
    {
        return Mage::helper('udropship')->getVendorShipmentCollection();
    }
    public function exportShipmentsAction()
    {
        $result = array();
        try {
            $shipments = $this->getVendorShipmentCollection();
            if (!$shipments->getSize()) {
                Mage::throwException('No purchase orders found for these criteria');
            }

            $batch = Mage::getModel('udbatch/batch')->addData(array(
                'batch_type' => 'export_orders',
                'batch_status' => 'pending',
                'vendor_id' => $this->_getSession()->getVendor()->getId(),
                'use_custom_template' => Mage::getStoreConfig('udropship/batch/statement_export_template')
            ));

            foreach ($shipments as $po) {
                $batch->addPOToExport($po);
            }

            $output = $batch->getAdapter()->renderOutput();

            Mage::helper('udropship')->sendDownload('orders_export.csv', $output, 'text/csv');
            return;

        } catch (Exception $e) {
            if ($this->getRequest()->getParam('use_json_response')) {
                $result = array(
                    'error'=>true,
                    'message'=>$e->getMessage()
                );
            } else {
                $this->_getSession()->addError(Mage::helper('udropship')->__($e->getMessage()));
            }
        }
        if ($this->getRequest()->getParam('use_json_response')) {
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode($result)
            );
        } else {
            $this->_redirect('udropship/vendor/', array('_current'=>true, '_query'=>array('submit_action'=>'')));
        }
    }
    public function getVendorPoCollection()
    {
        return Mage::helper('udpo')->getVendorPoCollection();
    }
    public function getVendorPoShipmentCollection()
    {
        return Mage::helper('udpo')->getVendorShipmentCollection();
    }
    public function exportOrdersAction()
    {
        $result = array();
        try {
            $udpos = $this->getVendorPoCollection();
            if (!$udpos->getSize()) {
                Mage::throwException('No purchase orders found for these criteria');
            }

            $batch = Mage::getModel('udbatch/batch')->addData(array(
                'batch_type' => 'export_orders',
                'batch_status' => 'pending',
                'vendor_id' => $this->_getSession()->getVendor()->getId(),
                'use_custom_template' => Mage::getStoreConfig('udropship/batch/statement_export_template')
            ));

            foreach ($udpos as $po) {
                $batch->addPOToExport($po);
            }

            $output = $batch->getAdapter()->renderOutput();

            Mage::helper('udropship')->sendDownload('orders_export.csv', $output, 'text/csv');
            return;

        } catch (Exception $e) {
            if ($this->getRequest()->getParam('use_json_response')) {
                $result = array(
                    'error'=>true,
                    'message'=>$e->getMessage()
                );
            } else {
                $this->_getSession()->addError(Mage::helper('udropship')->__($e->getMessage()));
            }
        }
        if ($this->getRequest()->getParam('use_json_response')) {
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode($result)
            );
        } else {
            $this->_redirect('udpo/vendor/', array('_current'=>true, '_query'=>array('submit_action'=>'')));
        }
    }
	public function importOrdersPostAction()
    {
    	$r = $this->getRequest();
    	$hlp = Mage::helper('udropship');
    	$bHlp = Mage::helper('udbatch');
    	try {
    		$r->setParam('vendor_id', $this->_getSession()->getVendor()->getId());
    		$r->setParam('batch_type', 'import_orders');
        	$bHlp->processPost();
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            if ($bHlp->getBatch()) {
            	$this->_getSession()->addError(
            		$bHlp->getBatch()->getErrorInfo($e->getMessage())
            	);
            }
        }
        if ($bHlp->getBatch() != null && $bHlp->getBatch()->getStatus() == 'success') {
        	$this->_getSession()->addSuccess($hlp->__('Processed %s import rows', $bHlp->getBatch()->getNumRows()));
        }
        $this->_redirect('udbatch/vendor_batch/importOrders');
    }
	public function importStockPostAction()
    {
    	$r = $this->getRequest();
    	$hlp = Mage::helper('udropship');
    	$bHlp = Mage::helper('udbatch');

        $vsAttr = Mage::getStoreConfig('udropship/vendor/vendor_sku_attribute');
        $allowVendorSku = Mage::helper('udropship')->isUdmultiAvailable() || (!empty($vsAttr) && $vsAttr != 'sku');

        if (!empty($vsAttr)) {
            $vsAttr = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $vsAttr);
        }

    	try {
	    	if (!empty($_FILES['import_inventory_upload']['tmp_name'])) {
	            $filename = Mage::getConfig()->getVarDir('udbatch').'/'.$_FILES['import_inventory_upload']['name'];
	            @move_uploaded_file($_FILES['import_inventory_upload']['tmp_name'], $filename);
	        }
	        if ($r->getParam('import_inventory_textarea')) {
	            $filename = Mage::getConfig()->getVarDir('udbatch').'/import_inventory-'.date('YmdHis').'.txt';
	            @file_put_contents($filename, $r->getParam('import_inventory_textarea'));
	        }
	        if (!isset($filename)) {
	        	Mage::throwException(Mage::helper('udropship')->__('Empty input'));
	        }
        	if (!($delimiter = $r->getParam('import_inventory_delimiter'))) {
        		Mage::throwException(Mage::helper('udropship')->__('Empty delimiter'));
        	}
        	if (!($enclosure = $r->getParam('import_inventory_enclosure'))) {
        		Mage::throwException(Mage::helper('udropship')->__('Empty enclosure'));
        	}
        	if (!($fields = $r->getParam('import_inventory_fields')) || !is_array($fields)) {
        		Mage::throwException(Mage::helper('udropship')->__('Empty fields'));
        	}
        	if (!in_array('sku', $fields) && (!$allowVendorSku || !in_array('vendor_sku', $fields))) {
                if ($allowVendorSku) {
        		    Mage::throwException(Mage::helper('udropship')->__('Required "%s" (or "%s") field is not specified', 'sku', 'vendor_sku'));
                } else {
                    Mage::throwException(Mage::helper('udropship')->__('Required "%s" field is not specified', 'sku'));
                }
        	}
        	foreach ($fields as $i=>$field) {
        		if (!empty($field) && !$this->isAllowedField($fields[$i])) {
        			$this->_getSession()->addNotice(Mage::helper('udropship')->__('"%s" field is not allowed. will be ignored.', $field));
        		}
        	}
            $content = file_get_contents($filename);

            $_content = preg_split("/\r\n|\n\r|\r|\n/", $content);
            if ($_content !== false) {
                $content = implode("\n", $_content);
            }

            $fp = fopen('php://temp', 'r+');
            fwrite($fp, $content);
            rewind($fp);

        	$rows = array();
        	while (!feof($fp)) {
	            $row = @fgetcsv($fp, 0, $delimiter, $enclosure);
	            if ($row === false && feof($fp)) break;
	            if ($row === false) {
	                $rows[] = false;
	                continue;
	            }
	            $fRow = array_filter($row);
	            if (empty($fRow)) {
	            	$rows[] = '';
	                continue;
	            }
	            $_row = array();
	            foreach ($row as $i=>$v) {
	                if (!empty($fields[$i]) && $this->isAllowedField($fields[$i])) {
	                    $_row[$fields[$i]] = $v;
	                }
	            }
	            $rows[] = $_row;
	        }
	        fclose($fp);
	        $duplicateSku = $missingReqField = $invalidRows = array();
	        $skus = array();
            $vendorSkus = array();
	    	foreach ($rows as $i=>$r) {
	            if ($r === false) {
	            	$invalidRows[] = $i+1;
	                continue;
	            }
	            if ($r === '') {
	            	$emptyRows[] = $i+1;
	            	continue;
	            }
	            if (empty($r['sku']) && (!$allowVendorSku || empty($r['vendor_sku']))) {
	            	$missingReqField[] = $i+1;
	            	continue;
	            }
                if (!empty($r['sku'])) {
                    if (!empty($skus[$r['sku']])) {
                        $duplicateSku[] = $i+1;
                        continue;
                    }
                    $skus[$r['sku']] = $i;
                } elseif (!empty($r['vendor_sku'])) {
                    if (!empty($vendorSkus[$r['vendor_sku']])) {
                        $duplicateSku[] = $i+1;
                        continue;
                    }
                    $vendorSkus[$r['vendor_sku']] = $i;
                }
	        }
	        $_res  = Mage::getResourceSingleton('udbatch/batch');
	        $_conn = $_res->getReadConnection();

	    	$_skus = array();
            foreach ($skus as $sku=>$p) {
                $_skus[] = is_numeric($sku) ? "'".$sku."'" : $_conn->quote($sku);
            }
            $_vendorSkus = array();
            foreach ($vendorSkus as $sku=>$p) {
                $_vendorSkus[] = is_numeric($sku) ? "'".$sku."'" : $_conn->quote($sku);
            }
            $skuPids = array();
            $skuPidUnions = array();
            if (!empty($_skus)) {
                $skuPidUnions[] = $_conn->select()
                    ->from($_res->getTable('catalog/product'), array('sku', 'entity_id'))
                    ->where('sku in ('.join(',', $_skus).')');
            }
            if (!empty($_vendorSkus) && $allowVendorSku) {
                $_tmpSel = $_conn->select()
                    ->from(array('p' => $_res->getTable('catalog/product')), array());
                if (Mage::helper('udropship')->isUdmultiAvailable()) {
                    $_tmpSel->join(
                        array('vp' => $_res->getTable('udropship/vendor_product')),
                        'vp.product_id=p.entity_id and vp.vendor_id='.$this->_getSession()->getVendor()->getId(),
                        array()
                    );
                    $_tmpSel->where('vp.vendor_sku in ('.join(',', $_vendorSkus).')');
                    $_tmpSel->columns(array('vp.vendor_sku', 'entity_id'));
                } else {
                    $_tmpSel->join(
                        array('vp' => $vsAttr->getBackendTable()),
                        'vp.entity_id=p.entity_id and vp.store_id=0 and vp.attribute_id='.$vsAttr->getId(),
                        array()
                    );
                    $_tmpSel->where('vp.value in ('.join(',', $_vendorSkus).')');
                    $_tmpSel->columns(array('vp.value', 'entity_id'));
                }
                $skuPidUnions[] = $_tmpSel;
            }
            if (!empty($skuPidUnions)) {
                $skuPidUnionSel = $_conn->select()->union($skuPidUnions);
                $skuPids = $_conn->fetchPairs($skuPidUnionSel);
            }

	        $notFoundProducts = $notAssociatedProducts = array();
	        $updateRequest = array();
	        foreach ($rows as $i=>&$r) {
	        	if (empty($r)) continue;
                $skuKey = !empty($r['sku']) ? 'sku' : 'vendor_sku';
	        	if (empty($skuPids[$r[$skuKey]])) {
	        		$notFoundProducts[] = $i+1;
	        		continue;
	        	}
		        if (!in_array($skuPids[$r[$skuKey]], $this->_getSession()->getVendor()->getAssociatedProductIds())) {
		        	$notAssociatedProducts[] = $i+1;
		        	continue;
	        	}
	            $updateRequest[$skuPids[$r[$skuKey]]] = $rows[$i];
	        }
	        unset($r);

	        if (!empty($invalidRows)) {
	        	$this->_getSession()->addError(Mage::helper('udropship')->__('Invalid rows: %s', implode(',', $invalidRows)));
	        }
    		if (!empty($emptyRows)) {
	        	$this->_getSession()->addError(Mage::helper('udropship')->__('Empty rows: %s', implode(',', $emptyRows)));
	        }
    		if (!empty($missingReqField)) {
	        	$this->_getSession()->addError(Mage::helper('udropship')->__('Missing required field in rows: %s', implode(',', $missingReqField)));
	        }
    		if (!empty($duplicateSku)) {
	        	$this->_getSession()->addError(Mage::helper('udropship')->__('Duplicate sku within file in rows: %s', implode(',', $duplicateSku)));
	        }
    		if (!empty($notFoundProducts)) {
	        	$this->_getSession()->addError(Mage::helper('udropship')->__('Product not found for sku in rows: %s', implode(',', $notFoundProducts)));
	        }
    		if (!empty($notAssociatedProducts)) {
	        	$this->_getSession()->addError(Mage::helper('udropship')->__('Products not associated with vendor in rows: %s', implode(',', $notAssociatedProducts)));
	        }

	        $cnt = 0;
    		if (!empty($updateRequest)) {
                if (Mage::helper('udropship')->isUdmultiAvailable()) {
                    $cnt = Mage::helper('udmulti')->saveThisVendorProductsPidKeys($updateRequest, $this->_getSession()->getVendor());
                }
                if (!Mage::helper('udropship')->isUdmultiActive()) {
                    $cnt = Mage::helper('udropship')->saveThisVendorProducts($updateRequest, $this->_getSession()->getVendor());
                }
	        }
    		if ($cnt) {
                $this->_getSession()->addSuccess(Mage::helper('udropship')->__($cnt==1 ? '%s product was updated' : '%s products were updated', $cnt));
            } else {
                $this->_getSession()->addNotice(Mage::helper('udropship')->__('No updates were made'));
            }

    	} catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('udbatch/vendor_batch/importStock');
    }

    public function isAllowedField($field)
    {
    	return in_array($field, $this->getAllowedFields());
    }
    public function getAllowedFields()
    {
    	return array('sku', 'vendor_sku', 'stock_qty', 'stock_qty_add', 'stock_status');
    }
}