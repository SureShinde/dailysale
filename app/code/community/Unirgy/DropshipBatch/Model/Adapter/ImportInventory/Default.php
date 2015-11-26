<?php

class Unirgy_DropshipBatch_Model_Adapter_ImportInventory_Default extends Unirgy_DropshipBatch_Model_Adapter_ImportInventory_Abstract
{
    public function init()
    {}
    public function import($content)
    {
        $this->init();
        $rows = $this->parse($content);
        if ('realtime' != $this->getVendor()->getBatchImportInventoryReindex()
            && Mage::helper('udropship')->isUdmultiAvailable()
        ) {
            Mage::helper('udmulti')->setReindexFlag(false);
        }
        while (!empty($rows)) {
            $rowsToProcess = array_splice($rows, 0, 1000);
            $this->process($rowsToProcess);
            $this->getBatch()->flushRowsLog();
            if (Mage::helper('udropship')->isUdmultiAvailable()) {
                Mage::helper('udmulti')->clearMultiVendorData();
            }
        }
        if (Mage::helper('udropship')->isUdmultiAvailable()) {
            Mage::helper('udmulti')->setReindexFlag(true);
        }
        if ('full' == $this->getVendor()->getBatchImportInventoryReindex()) {
            Mage::getSingleton('index/indexer')->getProcessByCode('cataloginventory_stock')->reindexEverything();
            Mage::getSingleton('index/indexer')->getProcessByCode('catalog_product_price')->reindexEverything();
            Mage::getSingleton('index/indexer')->getProcessByCode('udropship_vendor_product_assoc')->reindexEverything();
        } elseif ('manual' == $this->getVendor()->getBatchImportInventoryReindex()) {
            Mage::getSingleton('index/indexer')->getProcessByCode('cataloginventory_stock')->changeStatus(
                Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
            Mage::getSingleton('index/indexer')->getProcessByCode('catalog_product_price')->changeStatus(
                Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
            Mage::getSingleton('index/indexer')->getProcessByCode('udropship_vendor_product_assoc')->changeStatus(
                Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }
        return $this;
    }
    public function parse($content)
    {
        $hlp = Mage::helper('udbatch');

        $_content = preg_split("/\r\n|\n\r|\r|\n/", $content);
        if ($_content !== false) {
            $content = implode("\n", $_content);
        }

        $fp = fopen('php://temp', 'r+');
        fwrite($fp, $content);
        rewind($fp);

        $fields = $this->getInvImportFields();
        $rows = array();
        $idx = 0;
        while (!feof($fp)) {
            $r = @fgetcsv($fp, 0, $this->getVendor()->getBatchImportInventoryFieldDelimiter(), '"');
            if (!$idx++ && $this->getVendor()->getBatchImportInventorySkipHeader()) continue;
            if (!$r) {
                $rows[] = array('error'=>Mage::helper('udropship')->__('Invalid row format'));
                continue;
            }
            $row = array();
            foreach ($r as $i=>$v) {
                if (isset($fields[$i]) && $this->_isAllowedField($fields[$i])) {
                    $row[$fields[$i]] = trim($v);
                }
            }
            $rows[] = $row;
        }
        fclose($fp);

        return $rows;
    }

    public function process($rows)
    {
        $hlp = Mage::helper('udbatch');

        Mage::dispatchEvent(
            'udbatch_import_inventory_convert_before',
            array('batch'=>$this->getBatch(), 'adapter'=>$this, 'vars'=>array('rows'=>&$rows))
        );

        $vsAttr = Mage::getStoreConfig('udropship/vendor/vendor_sku_attribute');
        $allowVendorSku = Mage::helper('udropship')->isUdmultiAvailable() || (!empty($vsAttr) && $vsAttr != 'sku');

        if (!empty($vsAttr)) {
            $vsAttr = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $vsAttr);
        }

        $skus = array();
        $vendorSkus = array();
        foreach ($rows as $i=>&$r) {
            if (!empty($r['error'])) {
                continue;
            }
            if (!empty($r['stock_qty'])) {
                $r['stock_qty'] = trim($r['stock_qty']);
            }
            if (!empty($r['stock_qty_add'])) {
                $r['stock_qty_add'] = trim($r['stock_qty_add']);
            }
            if (empty($r['sku']) && (!$allowVendorSku || empty($r['vendor_sku']))) {
                if ($allowVendorSku) {
                    $r['error'] = Mage::helper('udropship')->__('Missing required field: sku or vendor_sku');
                } else {
                    $r['error'] = Mage::helper('udropship')->__('Missing required field: sku');
                }
                continue;
            }
            if (!empty($r['sku'])) {
                if (!empty($skus[$r['sku']])) {
                    $r['error'] = Mage::helper('udropship')->__('Duplicate sku within file');
                    continue;
                }
                $skus[$r['sku']] = $i;
            } elseif ($allowVendorSku && !empty($r['vendor_sku'])) {
                if (!empty($vendorSkus[$r['vendor_sku']])) {
                    $r['error'] = Mage::helper('udropship')->__('Duplicate vendor sku within file');
                    continue;
                }
                $vendorSkus[$r['vendor_sku']] = $i;
            }
            $r['use_reserved_qty'] = $this->getVendor()->getData("batch_import_inventory_use_reserved_qty");
        }
        unset($r);

        $_res = Mage::getResourceSingleton('udropship/helper');
        $_readCon = $_res->getReadConnection();
        $_writeCon = $_res->getWriteConnection();
        $_skus = array();
        foreach ($skus as $sku=>$p) {
            $_skus[] = is_numeric($sku) ? "'".$sku."'" : $_readCon->quote($sku);
        }
        $_vendorSkus = array();
        foreach ($vendorSkus as $sku=>$p) {
            $_vendorSkus[] = is_numeric($sku) ? "'".$sku."'" : $_readCon->quote($sku);
        }
        $skuPids = array();
        $skuPidUnions = array();
        if (!empty($_skus)) {
            $skuPidUnions[] = $_readCon->select()
                ->from($_res->getTable('catalog/product'), array('sku', 'entity_id'))
                ->where('sku in ('.join(',', $_skus).')');
        }
        if (!empty($_vendorSkus) && $allowVendorSku) {
            $_tmpSel = $_readCon->select()
                ->from(array('p' => $_res->getTable('catalog/product')), array());
            if (Mage::helper('udropship')->isUdmultiAvailable()) {
                $_tmpSel->join(
                    array('vp' => $_res->getTable('udropship/vendor_product')),
                    'vp.product_id=p.entity_id and vp.vendor_id='.$this->getVendorId(),
                    array()
                );
                $_tmpSel->where('vp.vendor_sku in ('.join(',', $_vendorSkus).')');
                $_tmpSel->columns(array('vp.vendor_sku', 'GROUP_CONCAT(p.entity_id)'));
                $_tmpSel->group('vp.vendor_sku');
            } else {
                $_tmpSel->join(
                    array('vp' => $vsAttr->getBackendTable()),
                    'vp.entity_id=p.entity_id and vp.store_id=0 and vp.attribute_id='.$vsAttr->getId(),
                    array()
                );
                $_tmpSel->where('vp.value in ('.join(',', $_vendorSkus).')');
                $_tmpSel->columns(array('vp.value', 'GROUP_CONCAT(p.entity_id)'));
                $_tmpSel->group('vp.value');
            }
            $skuPidUnions[] = $_tmpSel;
        }
        if (!empty($skuPidUnions)) {
            foreach ($skuPidUnions as &$skuPidUnion) {
                $skuPidUnion = "($skuPidUnion)";
            }
            unset($skuPidUnion);
            $skuPidUnionSel = $_readCon->select()->union($skuPidUnions);
	        $skuPids = $_readCon->fetchPairs($skuPidUnionSel);
        }

        $newAssocCfg = Mage::getStoreConfig('udropship/batch/invimport_allow_new_association');
        $newAssocAllowed = Mage::helper('udropship')->isUdmultiAvailable()
            && ($newAssocCfg==Unirgy_DropshipBatch_Model_Source::NEW_ASSOCIATION_YES
                || $newAssocCfg==Unirgy_DropshipBatch_Model_Source::NEW_ASSOCIATION_YES_MANUAL && $this->getBatch()->getManualFlag()
            );

        $newAssocSql = array();
        $updateRequest = array();
        $vskuMultipid = Mage::getStoreConfig('udropship/batch/invimport_vsku_multipid');
        foreach ($rows as $i=>&$r) {
            $skuKey = !empty($r['sku']) ? 'sku' : 'vendor_sku';
            $_newAssocAllowed = $skuKey=='sku' && $newAssocAllowed;
            $isVsKey = $skuKey == 'vendor_sku';
            $_pIdsStr = @$skuPids[$r[$skuKey]];
            $_pIds = explode(',', $_pIdsStr);
            $_pIdsCnt = count($_pIds);
            if (empty($r['sku']) && empty($r['vendor_sku'])) {
                $r['error'] = Mage::helper('udropship')->__('Neither sku or vendor_sku specified');
            } elseif (empty($r[$skuKey]) || empty($skuPids[$r[$skuKey]])) {
        		$r['error'] = Mage::helper('udropship')->__('Product not found for '.($isVsKey ? 'vendor ' : '').'sku "%s"', $r[$skuKey]);
            } elseif ($_pIdsCnt>1
                && $vskuMultipid == Unirgy_DropshipBatch_Model_Source::INVIMPORT_VSKU_MULTIPID_REPORT
            ) {
                $r['error'] = Mage::helper('udropship')->__('Vendor sku "%s" maps to multiple products (ids: "%s")', $r[$skuKey], $_pIdsStr);
        	} elseif (Mage::helper('udropship')->isUdmultiAvailable()
                && $_pIdsCnt==1
                && !in_array($skuPids[$r[$skuKey]], $this->getVendor()->getVendorTableProductIds())
            ) {
                if (!$_newAssocAllowed) {
        		    $r['error'] = Mage::helper('udropship')->__('Product with '.($isVsKey ? 'vendor ' : '').'sku "%s" does not associate with vendor', $r[$skuKey]);
                } else {
                    $newAssocSql[] = $_readCon->quote(array(
                        'product_id' => $skuPids[$r[$skuKey]],
                        'vendor_id' => $this->getVendor()->getId(),
                        'status' => Mage::helper('udmulti')->getDefaultMvStatus()
                    ));
                    $r['new_assoc'] = true;
                    if (count($newAssocSql)>1000) {
                        $_writeCon->query(sprintf(
                            "insert ignore into %s (product_id,vendor_id,status) values (%s)",
                            $_res->getTable('udropship/vendor_product'),
                            implode('),(', $newAssocSql)
                        ));
                        $newAssocSql = array();
                    }
                }
        	}
            foreach ($this->_getNumericFields() as $decKey) {
                if (!empty($r[$decKey])) {
                    $r[$decKey] = Mage::app()->getLocale()->getNumber($r[$decKey]);
                }
            }
            $r['product_id'] = !empty($r[$skuKey]) && !empty($skuPids[$r[$skuKey]]) ? $skuPids[$r[$skuKey]] : null;
            $this->getBatch()->addInvImportRowLog($r);
            if (empty($r['error'])) {
                foreach ($_pIds as $_pId) {
                    $_uRow = $rows[$i];
                    $_uRow['product_id'] = $_pId;
            	    $updateRequest[$_pId] = $_uRow;
                    if ($vskuMultipid == Unirgy_DropshipBatch_Model_Source::INVIMPORT_VSKU_MULTIPID_FIRST) break;
                }
            }
        }
        unset($r);

        if ($newAssocAllowed && $newAssocSql) {
            $_writeCon->query(sprintf(
                "insert ignore into %s (product_id,vendor_id,status) values (%s)",
                $_res->getTable('udropship/vendor_product'),
                implode('),(', $newAssocSql)
            ));
        }

        if (!empty($updateRequest)) {
        	if (Mage::helper('udropship')->isUdmultiAvailable()) {
                Mage::helper('udmulti')->saveThisVendorProductsPidKeys($updateRequest, $this->getVendor());
            }
            if (!Mage::helper('udropship')->isUdmultiActive()) {
                Mage::helper('udropship')->saveThisVendorProducts($updateRequest, $this->getVendor());
            }
        }

        return $this;
    }

    protected $_allFields = array('vendor_sku'=>0, 'vendor_cost'=>1, 'stock_qty'=>1, 'priority'=>1, 'shipping_price'=>1, 'vendor_price'=>1, 'state'=>0, 'status'=>1, 'vendor_title'=>0, 'avail_state'=>0, 'avail_date'=>0, 'special_price'=>1, 'special_from_date'=>0, 'special_to_date'=>0, 'sku'=>0, 'stock_qty_add'=>1,'backorders'=>1,'state_descr'=>0, 'stock_status'=>1);
    protected function _isAllowedField($field)
    {
        return array_key_exists($field, $this->_allFields);
    }
    protected $__numericFields;
    protected $_numericFields;
    protected function _initNumericFields()
    {
        if (null === $this->__numericFields) {
            foreach ($this->_allFields as $field=>$isNum) {
                if ($isNum) $this->__numericFields[$field] = $isNum;
            }
            $this->_numericFields = array_keys($this->__numericFields);
        }
        return $this;
    }
    protected function _isNumericField($field)
    {
        return array_key_exists($field, $this->__numericFields);
    }
    protected function _getNumericFields()
    {
        $this->_initNumericFields();
        return $this->_numericFields;
    }

    public function initInvImportFields()
    {
        return $this->_initInvImportFields(true);
    }
    protected $_initInvImportFields;
    protected function _initInvImportFields($refresh=false)
    {
        if (is_null($this->_initInvImportFields) || $refresh) {
            $tpl = $this->getVendor()->getBatchImportInventoryTemplate();
            if (($_useCustTpl = $this->getBatch()->getUseCustomTemplate())
                && ($_custTpl = Mage::helper('udbatch')->getManualImportTemplate($_useCustTpl))
            ) {
                $tpl = $_custTpl;
            }
            $this->setData('inv_import_template', $tpl);
            $this->getBatch()->setData('inv_import_template', $tpl);
            if (!preg_match_all('#\[([^]]+)\]([^[]+)?#', $tpl, $m, PREG_PATTERN_ORDER)) {
                Mage::throwException('Invalid import template');
            }
            if (!in_array('sku', $m[1]) && !in_array('vendor_sku', $m[1])) {
                Mage::throwException('Missing required field');
            }
            $this->setData('inv_import_fields', $m[1]);
            $this->getBatch()->setData('inv_import_fields', $m[1]);
            $this->setData('inv_import_delimiter', $m[2][0]);
            $this->getBatch()->setData('inv_import_delimiter', $m[2][0]);
            $this->_initImportFields = true;
        }
        return $this;
    }

    public function getInvImportFields()
    {
        $this->_initInvImportFields();
        return $this->getData('inv_import_fields');
    }

}
