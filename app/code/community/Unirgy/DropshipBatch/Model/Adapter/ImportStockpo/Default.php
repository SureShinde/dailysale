<?php

class Unirgy_DropshipBatch_Model_Adapter_ImportStockpo_Default extends Unirgy_DropshipBatch_Model_Adapter_ImportStockpo_Abstract
{
    public function init()
    {}
    public function parse($content)
    {
        $hlp = Mage::helper('udbatch');

        $fp = fopen('php://temp', 'r+');
        fwrite($fp, $content);
        rewind($fp);

        $fields = $this->getImportFields();
        $rows = array();
        $idx = 0;
        while (!feof($fp)) {
            $r = @fgetcsv($fp, 0, $this->getVendor()->getBatchImportStockpoFieldDelimiter(), '"');
            if (!$idx++ && $this->getVendor()->getBatchImportStockpoSkipHeader()) continue;
            if (!$r) {
                $rows[] = array('error'=>$hlp->__('Invalid row format'));
                continue;
            }
            $row = array();
            foreach ($r as $i=>$v) {
                if (isset($fields[$i])) {
                    $row[$fields[$i]] = $v;
                }
            }
            $rows[] = $row;
        }
        fclose($fp);

        return $rows;
    }

    public function initImportFields()
    {
        return $this->_initImportFields(true);
    }
    protected $_initImportFields;
    protected function _initImportFields($refresh=false)
    {
        if (is_null($this->_initImportFields) || $refresh) {
            $tpl = $this->getVendor()->getBatchImportStockpoTemplate();
            $this->setData('import_template', $tpl);
            $this->getBatch()->setData('import_template', $tpl);
            if (!preg_match_all('#\[([^]]+)\]([^[]+)?#', $tpl, $m, PREG_PATTERN_ORDER)) {
                Mage::throwException('Invalid import template');
            }
            if (!in_array('stockpo_id', $m[1])) {
                Mage::throwException('Missing required field');
            }
            $this->setData('import_fields', $m[1]);
            $this->getBatch()->setData('import_fields', $m[1]);
            $this->setData('import_delimiter', $m[2][0]);
            $this->getBatch()->setData('import_delimiter', $m[2][0]);
            $this->_initImportFields = true;
        }
        return $this;
    }

    public function getImportFields()
    {
        $this->_initImportFields();
        return $this->getData('import_fields');
    }

    protected function _validateRows(&$rows)
    {
        $hlp = Mage::helper('udbatch');
        $allowDupTrackIds = false;
        $poIds = array();
        $orderIds = array();
        $trackIds = array();
        foreach ($rows as $i=>&$r) {
            if (!empty($r['error'])) {
                continue;
            }
            if (empty($r['stockpo_id'])) {
                $r['error'] = $hlp->__('Missing required field');
                continue;
            }
            $stockPoIds[$r['stockpo_id']] = $i;
        }
        unset($r);

        return $stockPoIds;
    }

    public function process($rows)
    {
        $markAsShipped = in_array(
            $this->getVendor()->getData('batch_import_stockpo_po_status'),
            $this->getBatch()->getMarkAsShippedStatuses()
        );

        $hlp = Mage::helper('udbatch');
        $allowDupTrackIds = false;

        Mage::dispatchEvent(
            'udbatch_import_stockpo_convert_before',
            array('batch'=>$this->getBatch(), 'adapter'=>$this, 'vars'=>array('rows'=>&$rows))
        );

        $poIds = $this->_validateRows($rows);

        // find POs and orders
        $pos = Mage::getModel('udropship/po')->getCollection()
            ->addAttributeToFilter('ustock_vendor', $this->getVendorId())
            ->addAttributeToFilter('ustockpo_increment_id', array('in'=>array_keys($poIds)))
            ->addOrders()
            ->addStockPos();

        foreach ($pos as $po) {
            $order = $po->getOrder();
            $r = $rows[$poIds[$po->getUstockpoIncrementId()]];
            $this->getBatch()->addImportRowLog($order, $po, $r);
        }

        return $this;
    }

}
