<?php

class Unirgy_DropshipBatch_Model_Adapter_Default
    extends Unirgy_DropshipBatch_Model_Adapter_Abstract
{
    public function addRowLog($order, $po, $poItem)
    {
        $this->getBatch()->addRowLog($order, $po, $poItem);
        return $this;
    }
    public function addPO($po)
    {
        if (!$this->preparePO($po)) {
            return $this;
        }

        if (!$this->getItemsArr()) {
            $this->setItemsArr(array());
            $this->setTotalsArr(array());
        }
        $itemsFooter = $itemsFooterTpl = '';
        $itemTpl = $tpl = $this->getExportTemplate();

        if (($useItemTemplate = $this->getUseItemExportTemplate())) {
            $itemTpl = $this->getItemExportTemplate();
        }
        $itemsFooterTpl = $this->getItemFooterExportTemplate();

        $productIds = array();
        $udbatchLineNumber = 0;
        foreach ($po->getItemsCollection() as $item) {
            $orderItem = $item->getOrderItem();
            if (($orderItem->getChildren() || $orderItem->getChildrenItems() and $orderItem->getProductType() != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
                || ($orderItem->getParentItem() and $orderItem->getParentItem()->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
                || in_array($orderItem->getStatus(), $this->getSkipItemStatuses())) {
                continue;
            }
            $productIds[] = $item->getProductId();
            $item->setUdbatchLineNumber(++$udbatchLineNumber);
        }
        $po->setUdbatchTotalLines($udbatchLineNumber);

        $_oldStoreId = Mage::app()->getStore()->getId();
        Mage::app()->setCurrentStore(Mage::app()->getDefaultStoreView());
        $products = Mage::getModel('catalog/product')->getCollection()
            ->addIdFilter($productIds)
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite();

        $products->load();
        Mage::app()->setCurrentStore($_oldStoreId);

        $idx = 0;
        foreach ($po->getItemsCollection() as $item) {
            if ($product = $products->getItemById($item->getProductId())) {
                if (!$item->getProduct()) {
                    $item->setProduct($product);
                }
            }
            if (!$this->preparePOItem($item)) {
                continue;
            }
            $itemKey = $this->getVars('po_id').'-'.$item->getId();
            if ($useItemTemplate) {
                if (0==$idx++) {
                    $this->_data['items_arr'][$this->getVars('po_id').'-0'] = $this->renderTemplate($tpl, $this->getVars());
                }
            }
            $itemsFooter = $this->renderTemplate($itemsFooterTpl, $this->getVars());
            $this->_data['items_arr'][$itemKey] = $this->renderTemplate($itemTpl, $this->getVars());
            $this->addRowLog($this->getOrder(), $this->getPo(), $this->getPoItem());
            $this->restoreItem();
        }
        $this->_data['totals_arr'][$this->getVars('po_id')] = $this->getVars('po_totals');
        if ($itemsFooter) {
            $this->_data['items_arr'][$this->getVars('po_id').'-99999'] = $itemsFooter;
        }

        $this->setHasOutput(true);
        return $this;
    }

    public function renderOutput()
    {
        $batch = $this->getBatch();
        $header = $batch->getBatchType()=='export_orders' ? $this->getBatchExportOrdersHeader() : '';

        if ($this->getBatchExportOrdersTotalsTemplate()) {
            $grandTotals  = array();
            foreach ($this->_data['totals_arr'] as $poTotals) {
                foreach ($poTotals as $poTotKey=>$poTotVal) {
                    if (!isset($grandTotals[$poTotKey])) {
                        $grandTotals[$poTotKey] = $poTotVal;
                    } else {
                        $grandTotals[$poTotKey] += $poTotVal;
                    }
                }
            }
            $this->_data['items_arr'][] = $this->renderTemplate($this->getBatchExportOrdersTotalsTemplate(), array('grand_totals'=>$grandTotals));
        }
        if ($batch->getStatement() && $batch->getStatement()->getExtraAdjustments()) {
            $this->_data['items_arr'][] = '';
            $this->_data['items_arr'][] = Mage::helper('udropship')->__('Adjustments');
            foreach ($batch->getStatement()->getExtraAdjustments() as $stAdj) {
                $this->_data['items_arr'][] = '"'.implode('","', array(
                        $stAdj['adjustment_id'], $stAdj['po_type'], $stAdj['comment'],
                        Mage::helper('core')->formatDate($stAdj['created_at'], 'short'), $stAdj['amount']
                )).'"';
            }
            $this->_data['items_arr'][] = '';
            $this->_data['items_arr'][] = Mage::helper('udropship')->__('Statement Totals');
            $this->_data['items_arr'][] = '"'.implode('","', array(
                    Mage::helper('udropship')->__('Total Payout'), $batch->getStatement()->getTotalPayout()
                )).'"';
            if (Mage::helper('udropship')->isUdpayoutActive()) {
                $this->_data['items_arr'][] = '"'.implode('","', array(
                        Mage::helper('udropship')->__('Total Paid'), $batch->getStatement()->getTotalPaid()
                    )).'"';
                $this->_data['items_arr'][] = '"'.implode('","', array(
                        Mage::helper('udropship')->__('Total Due'), $batch->getStatement()->getTotalDue()
                    )).'"';
            }
        }

        $this->setHasOutput(false);
        return ($header ? $header."\n" : '') . join("\n", $this->getItemsArr());
    }

    public function getPerPoOutput()
    {
        $batch = $this->getBatch();
        $rows = array();
        $rows['header'] = $batch->getBatchType()=='export_orders' ? $this->getBatchExportOrdersHeader() : '';

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
