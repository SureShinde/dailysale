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
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
 
class Unirgy_DropshipPo_Model_Statement extends Unirgy_Dropship_Model_Vendor_Statement
{
    public function fetchOrders()
    {
        $core = Mage::helper('core');
        $vendor = $this->getVendor();
        $withholdOptions = Mage::getSingleton('udropship/source')->setPath('statement_withhold_totals')->toOptionHash();
        $withhold = array_flip((array)$vendor->getStatementWithholdTotals());
        $adjTrigger = 'ADJUSTMENT:';

        $res = Mage::getSingleton('core/resource');
        $pos = Mage::getResourceModel('udpo/po_grid_collection');
        $pos->getSelect()->join(
            array('t'=>$res->getTableName('udpo/po')),
            't.entity_id=main_table.entity_id'/*,
            array('udropship_vendor', 'udropship_available_at', 'udropship_method',
                'udropship_method_description', 'udropship_status', 'shipping_amount'
            )*/
        )
        ->where("udropship_vendor=?", $this->getVendorId())
        ->where("order_created_at>=?", $this->getOrderDateFrom())
        ->where("order_created_at<=?", $this->getOrderDateTo())
        ->order('main_table.entity_id asc');
        
        $adjustments = Mage::getModel('udpo/po_comment')->getCollection()
            ->addAttributeToFilter('parent_id', array('in'=>$pos->getAllIds()))
            ->addAttributeToFilter('comment', array('like'=>$adjTrigger.'%'))
        ;

        $orders = array();
        $totals = array(
            'subtotal'=>0, 'tax'=>0, 'shipping'=>0, 'handling'=>0,
            'com_amount'=>0, 'trans_fee'=>0, 'adj_amount'=>0, 'total_payout'=>0,
        );
        $poItemsToLoad = array();
        foreach ($pos as $id=>$po) {
            foreach (array('base_total_value', 'base_tax_amount') as $k) {
                if (!$po->hasData($k)) {
                    $poItemsToLoad[$id][$k] = true;
                }
            }

            $order = array(
                'po_id' => $po->getId(),
                'date' => $po->getOrderCreatedAt(),
                'id' => $po->getOrderIncrementId(),
                'subtotal' => $po->getBaseTotalValue(),
                'shipping' => $po->getShippingAmount(),
                'tax' => $po->getBaseTaxAmount(),
                'handling' => $po->getBaseHandlingFee(),
                'com_percent' => $po->getCommissionPercent(),
                'trans_fee' => $po->getTransactionFee(),
                'adj_amount' => 0,
            );

            Mage::dispatchEvent('udropship_vendor_statement_row', array(
                'po'=>$po,
                'order'=>&$order
            ));

            $orders[$id] = $order;
        }
        if ($poItemsToLoad) {
            $poItems = Mage::getModel('udpo/po_item')->getCollection();
            $poItems->getSelect()
                ->join(array('i'=>$res->getTableName('sales/order_item')), 'i.item_id=order_item_id', array('base_row_total', 'base_tax_amount'))
                ->where('order_item_id<>0 and parent_id in (?)', array_keys($poItemsToLoad))
            ;
            $itemTotals = array();
            foreach ($poItems as $item) {
                $id = $item->getParentId();
                if (empty($itemTotals[$id])) {
                    $itemTotals[$id] = array('subtotal'=>0, 'tax'=>0);
                }
                $itemTotals[$id]['subtotal'] += $item->getBaseRowTotal();
                $itemTotals[$id]['tax'] += $item->getBaseTaxAmount();
            }
            foreach ($itemTotals as $id=>$total) {
                foreach ($total as $k=>$v) {
                    $orders[$id][$k] = $v;
                }
            }
        }

        foreach ($orders as &$order) {
            if (is_null($order['com_percent'])) {
                $order['com_percent'] = $vendor->getCommissionPercent();
            }
            $order['com_percent'] *= 1;
            if (is_null($order['trans_fee'])) {
                $order['trans_fee'] = $vendor->getTransactionFee();
            }
            $order['com_amount'] = round($order['subtotal']*$order['com_percent']/100, 2);
            $order['total_payout'] = $order['subtotal']-$order['com_amount']-$order['trans_fee'];
            //+$order['tax']+$order['handling']+$order['shipping'];

            foreach ($withholdOptions as $k=>$l) {
                if (!isset($withhold[$k]) && isset($order[$k])) {
                    $order['total_payout'] += $order[$k];
                }
            }
            foreach (array_keys($totals) as $k) {
                $totals[$k] += $order[$k];
                $order[$k] = $core->formatPrice($order[$k], false);
            }
        }
        unset($order);

        $adjTriggerQ = preg_quote($adjTrigger);
        foreach ($adjustments as $adjustment) {
            if (!preg_match("#({$adjTriggerQ})\\s*([0-9.-]+)\\s*(.*)\$#m", $adjustment->getComment(), $match)) {
                continue;
            }
            $adj = array(
                'amount' => (float)$match[2],
                'comment' => $match[1].' '.$match[3],
            );
            $totals['adj_amount'] += $adj['amount'];
            $totals['total_payout'] += $adj['amount'];

            $adj['amount'] = $core->formatPrice($adj['amount'], false);
            $orders[$adjustment->getParentId()]['adjustments'][] = $adj;
        }

        $this->setTotalOrders(sizeof($orders));
        $this->setTotalPayout($totals['total_payout']);

        foreach ($totals as &$total) {
            $total = $core->formatPrice($total, false);
        }
        unset($total);

        $this->setOrdersData(Zend_Json::encode(compact('orders', 'totals')));
        return $this;
    }
}
