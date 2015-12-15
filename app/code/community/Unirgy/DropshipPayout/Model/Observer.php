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
 
class Unirgy_DropshipPayout_Model_Observer
{
    public function processStandard()
    {
        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        $payouts = Mage::getModel('udpayout/payout')->getCollection()
            ->setFlag('skip_offline', true)
            ->loadScheduledPayouts()
            ->addPendingPos(true)
            ->finishPayout()
            ->saveOrdersPayouts(true);

        try {
            $payouts->pay();
        } catch (Exception $e) {
            Mage::logException($e);
        }
        
            
        Mage::helper('udpayout')->generateSchedules()->cleanupSchedules();
    }
    
    public function udropship_adminhtml_vendor_tabs_after($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $id = $observer->getEvent()->getId();
        $v = Mage::helper('udropship')->getVendor($id);

        if (Mage::helper('udpayout')->isVendorEnabled($v)) {
            $block->addTab('payouts_section', array(
                'label'     => Mage::helper('udropship')->__('Payouts'),
                'title'     => Mage::helper('udropship')->__('Payouts'),
                'content'   => $block->getLayout()->createBlock('udpayout/adminhtml_vendor_payout_grid', 'udropship.payout.grid')
                    ->setVendorId($id)
                    ->toHtml(),
            ));
        }
    }

    public function adminhtml_version($observer)
    {
        Mage::helper('udropship')->addAdminhtmlVersion('Unirgy_DropshipPayout');
    }

    public function udropship_shipment_status_save_after($observer)
    {
        $this->_sales_order_shipment_save_after($observer);
    }
    public function sales_order_shipment_save_after($observer)
    {
        $this->_sales_order_shipment_save_after($observer);
    }
    protected function _sales_order_shipment_save_after($observer)
    {
        $po = $observer->getEvent()->getShipment();
        Mage::helper('udpayout/protected')->sales_order_shipment_save_after($po);
    }

    public function udpo_po_status_save_after($observer)
    {
        $this->_udpo_po_save_after($observer);
    }
    public function udpo_po_save_after($observer)
    {
        $this->_udpo_po_save_after($observer);
    }
    protected function _udpo_po_save_after($observer)
    {
        $po = $observer->getEvent()->getPo();
        Mage::helper('udpayout/protected')->udpo_po_save_after($po);
    }
    
    public function udropship_vendor_statement_save_before($observer)
    {
        $statement = $observer->getEvent()->getStatement();
        $statementId = $statement->getStatementId();
        if (!$statement->getId() && isset($this->_statementPayouts[$statementId])) {
            foreach ($this->_statementPayouts[$statementId] as $pt) {
                if ($pt->getPayoutStatus() != Unirgy_DropshipPayout_Model_Payout::STATUS_CANCELED
                    && $pt->getPayoutStatus() != Unirgy_DropshipPayout_Model_Payout::STATUS_PAID
                    && $pt->getPayoutStatus() != Unirgy_DropshipPayout_Model_Payout::STATUS_PAYPAL_IPN
                ) {
                    $pt->setPayoutStatus(Unirgy_DropshipPayout_Model_Payout::STATUS_HOLD);
                }
                $pt->setStatementId($statementId)->save();
            }
            $statement->getResource()->markPosHold($statement);
        }
    }
    
    protected $_statementPayouts;
    protected $_statementPayoutsByPo = array();
    public function udropship_vendor_statement_pos($observer)
    {
        $statement   = $observer->getEvent()->getStatement();
        $pos   = $observer->getEvent()->getPos();
        $statementId = $statement->getStatementId();
        $this->_statementPayouts[$statementId] = Mage::getResourceModel('udpayout/payout_collection')->loadStatementPayouts($statement, $pos);
        foreach ($this->_statementPayouts[$statementId] as $sp) {
            foreach ($sp->initTotals()->getOrders() as $sId=>$order) {
                $_sId = explode('-', $sId);
                $this->_statementPayoutsByPo[$statementId][$sId] = $sp;
                $this->_statementPayoutsByPo[$statementId][$_sId[0]] = $sp;
            }
        }
    }
    public function udropship_vendor_statement_row($observer)
    {
        $statementId = $observer->getEvent()->getStatement()->getStatementId();
        $sId = $observer->getEvent()->getPo()->getId();
        $eData = $observer->getEvent()->getData();
        $order = &$eData['order'];
        if (isset($this->_statementPayoutsByPo[$statementId][$sId])) {
            $order['paid'] = $this->_statementPayoutsByPo[$statementId][$sId]->getPayoutStatus()==Unirgy_DropshipPayout_Model_Payout::STATUS_PAID
                || $this->_statementPayoutsByPo[$statementId][$sId]->getPayoutStatus()==Unirgy_DropshipPayout_Model_Payout::STATUS_PAYPAL_IPN;
        }
    }
    public function udropship_vendor_statement_item_row($observer)
    {
        $statementId = $observer->getEvent()->getStatement()->getStatementId();
        $sId = $observer->getEvent()->getPo()->getId();
        $eData = $observer->getEvent()->getData();
        $order = &$eData['order'];
        if (isset($this->_statementPayoutsByPo[$statementId][$sId])) {
            $order['paid'] = $this->_statementPayoutsByPo[$statementId][$sId]->getPayoutStatus()==Unirgy_DropshipPayout_Model_Payout::STATUS_PAID
                || $this->_statementPayoutsByPo[$statementId][$sId]->getPayoutStatus()==Unirgy_DropshipPayout_Model_Payout::STATUS_PAYPAL_IPN;
        }
    }
    public function udropship_vendor_statement_collect_payouts($observer)
    {
        $statement   = $observer->getEvent()->getStatement();
        $statementId = $statement->getStatementId();
        $totalPaid = 0;
        $paymentPaid = 0;
        foreach ($this->_statementPayouts[$statementId] as $sp) {
            $statement->addPayout($sp);
            if ($sp->getPayoutStatus() == Unirgy_DropshipPayout_Model_Payout::STATUS_PAID
                || $sp->getPayoutStatus() == Unirgy_DropshipPayout_Model_Payout::STATUS_PAYPAL_IPN
            ) {
                $totalPaid += $sp->getTotalPaid();
                $paymentPaid += $sp->getPaymentPaid();
                foreach ($sp->getAdjustments(Mage::helper('udropship')->getAdjustmentPrefix('payout')) as $adj) {
                    $statement->addAdjustment($adj);
                }
            }
        }
        $statement->setPaymentPaid($statement->getPaymentPaid()+$paymentPaid);
        $statement->setTotalPaid($statement->getTotalPaid()+$totalPaid);
    }
}
