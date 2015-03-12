<?php

class Unirgy_DropshipBatch_Model_Adapter_ExportStockpo_Default
    extends Unirgy_DropshipBatch_Model_Adapter_ExportStockpo_Abstract
{
    public function addPO($po)
    {
        if (!$this->preparePO($po)) {
            return $this;
        }

        if (!$this->getItemsArr()) {
            $this->setItemsArr(array());
        }
        $tpl = $this->getExportTemplate();

        foreach ($po->getItemsCollection() as $item) {
            if (!$this->preparePOItem($item)) {
                continue;
            }
            $itemKey = $this->getVars('po_id').'-'.$item->getId();
            $this->_data['items_arr'][$itemKey] = $this->renderTemplate($tpl, $this->getVars());
            $this->getBatch()->addRowLog($this->getOrder(), $this->getPo(), $this->getPoItem());
            $this->restoreItem();
        }

        $this->setHasOutput(true);
        return $this;
    }

    public function renderOutput()
    {
        $batch = $this->getBatch();
        $header = $batch->getBatchType()=='export_stockpo' ? $batch->getVendor()->getBatchExportStockpoHeader() : '';

        $this->setHasOutput(false);
        return ($header ? $header."\n" : '') . join("\n", $this->getItemsArr());
    }

    public function getPerPoOutput()
    {
        $batch = $this->getBatch();
        $rows = array();
        $rows['header'] = $batch->getBatchType()=='export_stockpo' ? $batch->getVendor()->getBatchExportStockpoHeader() : '';

        foreach ($this->getItemsArr() as $iKey => $iRow) {
            $poId = substr($iKey, 0, strpos($iKey, '-'));
            if (empty($rows[$poId])) {
                $rows[$poId] = '';
            } else {
                $rows[$poId] .= "\n";
            }
            $rows[$poId] .= $iRow;
        }

        $this->setHasOutput(false);

        return $rows;
    }

}
