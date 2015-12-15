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
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
 
class Unirgy_DropshipPayout_Model_Mysql4_Payout_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected $_payouts = array();
    protected $_dateFrom;
    protected $_dateTo;

    protected function _construct()
    {
        $this->_init('udpayout/payout');
        parent::_construct();
    }
    
    public function setDateFrom($date)
    {
        $this->_dateFrom = $date;
        return $this;
    }
    public function setDateTo($date)
    {
        $this->_dateTo = $date;
        return $this;
    }
    
    public function resetPayouts()
    {
        $this->_payouts = array();
    }
    
    public function loadStatementPayouts($statement, $pos)
    {
        $conn = $this->getConnection();
        $sSelect = $this->getSelect()
            ->join(array('pr' => $this->getTable('udpayout/payout_row')), 'main_table.payout_id=pr.payout_id', array())
            ->where('pr.po_id in (?)', $pos->getAllIds())
            ->where('payout_status!=?', Unirgy_DropshipPayout_Model_Payout::STATUS_CANCELED);
        $pSelect = $conn->select()
            ->from($this->getTable('udpayout/payout'))
            ->where('statement_id=?', $statement->getStatementId())
            ->where('payout_status!=?', Unirgy_DropshipPayout_Model_Payout::STATUS_CANCELED);
        $this->_select = $conn->select()->union(array("($sSelect)", "($pSelect)"));

        return $this;
    }
    
    public function loadScheduledPayouts()
    {
        $hlp = Mage::helper('udropship');
        $ptHhlp = Mage::helper('udpayout');

        // find all scheduled payouts scheduled for earlier than now, sorted by schedule time
        $this->addFieldToFilter('payout_status', 'scheduled')
            ->addFieldToFilter('scheduled_at', array('datetime'=>true, 'to'=>now()));
        $this->getSelect()->order('scheduled_at');

        // preprocess payouts and set correct statuses
        foreach ($this->getItems() as $p) {
            $this->addPayout($p, true);
        }

        $this->cleanPayouts();

        return $this;
    }
    
    public function cleanPayouts()
    {
        foreach($this->getItems() as $p) {
            if ($p->getPayoutStatus()!='processing') {
                $this->removePayout($p);
            }
        }
        return $this;
    }

    public function addExternalPayout($payout, $validate=false)
    {
        $this->_setIsLoaded();
        $this->addItem($payout);
        return $this->_addPayout($payout, $validate);
    }
    
    public function addPayout($payout, $validate=false)
    {
        $payout->setPayoutStatus('processing');
        return $this->_addPayout($payout, $validate);
    }
    
    protected function _addPayout($payout, $validate=false)
    {
        $vId = $payout->getVendorId();
        if ($validate) {
            // if vendors are not configured to be scheduled anymore, mark as canceled
            if (!Mage::helper('udpayout')->isVendorEnabled(Mage::helper('udropship')->getVendor($vId), true)) {
                $payout->setPayoutStatus('canceled')->save();
                return $this;
            }
            // if multiple payouts for the same vendor exist, mark older payouts as missed
            elseif (!empty($this->_payouts[$vId])) {
                $this->_payouts[$vId]->delete();
            }
        }
        $this->_payouts[$vId] = $payout;
        return $this;
    }

    public function removePayout($payout)
    {
        $this->removeItemByKey($payout->getId());
        return $this;
    }

    public function addPendingPos($vendorIds=null)
    {
        if ($vendorIds===true) {
            $vendorIds = array_keys($this->_payouts);
        }
        $hlp = Mage::helper('udropship');
        $ptHlp = Mage::helper('udpayout');
        foreach ($vendorIds as $vId) {
            $ptPoStatuses = $hlp->getVendor($vId)->getPayoutPoStatus();
            if (!is_array($ptPoStatuses)) {
                $ptPoStatuses = explode(',', $ptPoStatuses);
            }
            $poType = $hlp->getVendor($vId)->getStatementPoType();
            Mage::getResourceSingleton('udpayout/payout')->fixStatementDate($hlp->getVendor($vId), $poType, $ptPoStatuses, $this->_dateFrom, $this->_dateTo);
            if ($hlp->isSalesFlat()) {
                $res = Mage::getSingleton('core/resource');
                $pos = $poType == 'po' ? Mage::getResourceModel('udpo/po_grid_collection') : Mage::getResourceModel('sales/order_shipment_grid_collection');
                $pos->getSelect()->join(
                    array('t'=>$poType == 'po' ? $res->getTableName('udpo/po') : $res->getTableName('sales/shipment')),
                    't.entity_id=main_table.entity_id'
                );
                $pos->addAttributeToFilter('main_table.udropship_payout_status', array('null'=>true));
                $pos->addAttributeToSort('main_table.entity_id', 'asc');
                $pos->addAttributeToFilter('t.udropship_vendor', $vId);
                $pos->addAttributeToFilter('t.udropship_status', array('in'=>$ptPoStatuses));
            } else {
                $pos = Mage::getModel('sales/order_shipment')->getCollection()
                    ->addAttributeToSelect('*')
                    ->joinAttribute('order_increment_id', 'order/increment_id', 'order_id')
                    ->joinAttribute('order_created_at', 'order/created_at', 'order_id')
                    ->addAttributeToFilter('udropship_payout_status', array('null'=>true), 'left')
                    ->addAttributeToSort('entity_id', 'asc')
                    ->addAttributeToFilter('udropship_vendor', $vId)
                    ->addAttributeToFilter('udropship_status', array('in'=>$ptPoStatuses));
            }
            if (isset($this->_dateFrom)) {
                if ($hlp->isSalesFlat()) {
                    $pos->getSelect()
                        ->where("t.statement_date IS NOT NULL")
                        ->where("t.statement_date!='0000-00-00 00:00:00'")
                        ->where("t.statement_date>=?", $this->_dateFrom)
                        ->where("t.statement_date<=?", $this->_dateTo);
                } else {
                    $pos->addAttributeToFilter('statement_date', array('notnull'=>true));
                    $pos->addAttributeToFilter('statement_date', array('neq'=>'0000-00-00 00:00:00'));
                    $pos->addAttributeToFilter('statement_date', array(
                        'date' => true,
                        'from' => $this->_dateFrom,
                        'to' => $this->_dateTo,
                    ));
                }
            }

            Unirgy_DropshipPayout_Model_Payout::processPos($pos, $hlp->getVendor($vId)->getStatementSubtotalBase());
            
            foreach ($pos as $po) {
                $this->addPo($po);
            }
        }

        return $this;
    }
    
    public function finishPayout()
    {
        foreach ($this as $payout) {
            $payout->finishPayout();
        }
        return $this;
    }
    
    public function save()
    {
        foreach ($this->getItems() as $item) {
            try {
                $item->save()->setIsJustSaved(true);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }
    
    public function saveOrdersPayouts($deleteEmpty=false)
    {
        foreach ($this->getItems() as $item) {
            if (count($item->getOrders())==0) {
                $this->removePayout($item);
                if ($deleteEmpty) $item->delete();
            } else {
                $item->save();
            }
        }
        return $this;
    }
    
    public function pay()
    {
        $ptHlp = Mage::helper('udpayout');
        $ptPerMethod = array();
        $ptMethods = array();
        foreach ($this as $pt) {
            if ($pt->getPayoutStatus() == Unirgy_DropshipPayout_Model_Payout::STATUS_PAID) { 
                $pt->addMessage(
                    Mage::helper('udropship')->__("This payout already paid")
                );
                $pt->save();
                continue;
            }
            if ($pt->getPayoutStatus() == Unirgy_DropshipPayout_Model_Payout::STATUS_CANCELED) { 
                $pt->addMessage(
                    Mage::helper('udropship')->__("This payout is canceled")
                );
                $pt->save();
                continue;
            }
            if ($pt->getPayoutStatus() == Unirgy_DropshipPayout_Model_Payout::STATUS_PAYPAL_IPN) { 
                $pt->addMessage(
                    Mage::helper('udropship')->__("This payout wait paypal IPN")
                );
                $pt->save();
                continue;
            }
            if ($pt->getTotalDue()<=0) {
                $pt->addMessage(
                    Mage::helper('udropship')->__('Payout "total due" must be positive'),
                    Unirgy_DropshipPayout_Model_Payout::STATUS_ERROR
                );
                $pt->save();
                continue;
            }
            if (!$pt->getPayoutMethod()) {
                $pt->addMessage(
                    Mage::helper('udropship')->__('Empty payout method'),
                    Unirgy_DropshipPayout_Model_Payout::STATUS_ERROR
                );
                $pt->save();
                continue;
            }
            if (!isset($ptMethods[$pt->getPayoutMethod()])) {
                $pmNode = Mage::getConfig()->getNode('global/udropship/payout/method/'.$pt->getPayoutMethod());
                $methodClass = $pmNode->getClassName();
                if (!class_exists($methodClass)) {
                    $pt->addMessage(
                        Mage::helper('udropship')->__("Can't find payout method class"),
                        Unirgy_DropshipPayout_Model_Payout::STATUS_ERROR
                    );
                    $pt->save();
                    continue;
                }
                $ptMethods[$pt->getPayoutMethod()] = new $methodClass;
            }
            $ptPerMethod[$pt->getPayoutMethod()][] = $pt;
        }
        foreach ($ptMethods as $ptMethodId => $ptMethod) {
            try {
                if ($this->getFlag('skip_offline') && !$ptMethod->isOnline()) continue;
                $ptMethod->pay($ptPerMethod[$ptMethodId]);
                foreach ($ptPerMethod[$ptMethodId] as $pt) {
                    if ($pt->hasPayoutMethodErrors()) {
                        $pt->addMessage(
                            implode("\n", $pt->PayoutMethodErrors()), 
                            Unirgy_DropshipPayout_Model_Payout::STATUS_ERROR
                        );
                        $pt->save();
                    } else {
                        if (!Mage::getStoreConfigFlag('udropship/payout_paypal/use_ipn')) {
                            $pt->afterPay();
                        } else {
                            $pt->addMessage(Mage::helper('udropship')->__('Successfully send payment. Waiting for IPN to complete.'), Unirgy_DropshipPayout_Model_Payout::STATUS_PAYPAL_IPN)->setIsJustPaid(true);
                        }
                        $pt->save();
                    }
                }
            } catch (Exception $e) {
                foreach ($ptPerMethod[$ptMethodId] as $pt) {
                    $pt->addMessage($e->getMessage(), Unirgy_DropshipPayout_Model_Payout::STATUS_ERROR)->save();
                }
                Mage::logException($e);
            }
        }
        return $this;
    }

    public function addPo($po)
    {
        $vId = $po->getUdropshipVendor();
        if (empty($this->_payouts[$vId])) {
            $payout = false;
            foreach ($this->getItems() as $item) {
                if ($item->getVendorId()==$vId
                    && $item->getPayoutStatus()=='processing'
                ) {
                    $payout = $item;
                    break;
                }
            }
            if (!$payout) {
                $payout = Mage::getModel('udpayout/payout')->setVendorId($vId);
                $this->addItem($payout);
            }
            $this->_payouts[$vId] = $payout;
        } else {
            $payout = $this->_payouts[$vId];
        }
        $payout->addPo($po);
        return $this;
    }

}
