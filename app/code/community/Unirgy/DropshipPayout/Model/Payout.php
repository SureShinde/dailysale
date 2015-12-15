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

class Unirgy_DropshipPayout_Model_Payout extends Unirgy_Dropship_Model_Vendor_Statement_Abstract implements Unirgy_Dropship_Model_Vendor_Statement_Interface
{
    protected $_eventPrefix = 'udpayout_payout';
    protected $_eventObject = 'payout';
    
    const TYPE_AUTO      = 'auto';
    const TYPE_MANUAL    = 'manual';
    const TYPE_SCHEDULED = 'scheduled';
    const TYPE_STATEMENT = 'statement';
    
    const STATUS_PENDING    = 'pending';
    const STATUS_SCHEDULED  = 'scheduled';
    const STATUS_PROCESSING = 'processing';
    const STATUS_HOLD       = 'hold';
    const STATUS_PAYPAL_IPN = 'paypal_ipn';
    const STATUS_PAID       = 'paid';
    const STATUS_ERROR      = 'error';
    const STATUS_CANCELED   = 'canceled';

    protected function _construct()
    {
        $this->_init('udpayout/payout');
    }
    
    public function getAdjustmentPrefix()
    {
        return Mage::helper('udropship')->getAdjustmentPrefix('payout');
    }
    
    public function isMyAdjustment($adjustment)
    {
        return 0 === strpos($adjustment->getAdjustmentId(), $this->getAdjustmentPrefix())
            || 0 === strpos($adjustment->getAdjustmentId(), Mage::helper('udropship')->getAdjustmentPrefix('statement:payout'));
    }
    
    protected $_statement;
    public function getStatement()
    {
        if (is_null($this->_statement)) {
            $this->_statement = Mage::getModel('udropship/vendor_statement')->load($this->getStatementId(), 'statement_id');
        }
        return $this->_statement;
    }
    public function setStatement($statement)
    {
        $this->_statement = $statement;
        return $this;
    }

    public function addPo($po)
    {
        $core = Mage::helper('core');
        $hlp = Mage::helper('udropship');
        $ptHlp = Mage::helper('udpayout');
        $vendor = $this->getVendor();

        $this->initTotals();

        $hlp->collectPoAdjustments(array($po));
        
        $sId = $po->getId();
        $order = $this->initOrder($po);
    
        Mage::dispatchEvent('udropship_vendor_payout_row', array(
            'payout'   => $this,
            'po' => $po,
            'order'    => &$order
        ));
        
        $order = $this->calculateOrder($order);
        $this->_totals_amount = $this->accumulateOrder($order, $this->_totals_amount);

        $poId = $po->getId() ? $po->getId() : spl_object_hash($po);
        $this->_orders[$poId] = $order;

        return $this;
    }
    
    public function finishPayout()
    {
        return $this->finishStatement();
    }

    protected function _getEmptyTotals($format=false)
    {
        return Mage::helper('udpayout')->getEmptyPayoutTotals($format);
    }
    
    protected function _getEmptyCalcTotals($format=false)
    {
        return Mage::helper('udpayout')->getEmptyPayoutCalcTotals($format);
    }
    
    public function getAdjustmentClass()
    {
        if (is_null($this->_adjustmentClass)) {
            $this->_adjustmentClass = Mage::getConfig()->getModelClassName('udpayout/payout_adjustment');
        }
        return $this->_adjustmentClass;
    }
    
    public function pay()
    {
        Mage::helper('udpayout/protected')->payoutPay($this);
        return $this;
    }
    
    public function afterPay()
    {
        $this->markDueAmountsPaid($this);
        $this->addMessage(Mage::helper('udropship')->__('Successfully paid'), self::STATUS_PAID)->setIsJustPaid(true);
        $this->initTotals();
        foreach ($this->_orders as &$order) {
            $order['paid'] = true;
        }
        unset($order);
        if ($this->getPayoutType() == self::TYPE_STATEMENT
            && ($statement = Mage::getModel('udropship/vendor_statement')->load($this->getStatementId(), 'statement_id'))
            && $statement->getId()
        ) {
            $statement->completePayout($this);
        }
        return $this;
    }
    
    public function addMessage($message, $status=null)
    {
        $ei = sprintf("%s\n[%s] %s",
            $this->getErrorInfo(),
            Mage::app()->getLocale()->date(),
            $message
        );
        $this->setErrorInfo(ltrim($ei));
        if (!empty($status)
            && $this->getPayoutStatus() != self::STATUS_PAID
            && ($this->getPayoutStatus() != self::STATUS_PAYPAL_IPN || $status == self::STATUS_PAID)
        ) {
            $this->setPayoutStatus($status);
        }
        return $this;
    }
    
    public function setPayoutStatus($status)
    {
        if ($status==self::STATUS_HOLD) {
            $this->setData('before_hold_status', $this->getPayoutStatus());
        }
        return $this->setData('payout_status', $status);
    }
    
    public function cancel()
    {
        $this->setCleanPayoutFlag(true)->setPayoutStatus(self::STATUS_CANCELED)->save();
        return $this;
    }

    public function getMethodInstance()
    {
        $pmNode = Mage::getConfig()->getNode('global/udropship/payout/method/'.$this->getPayoutMethod());
        if (!$pmNode) {
            return false;
        }
        $methodClass = $pmNode->getClassName();
        if (!class_exists($methodClass)) {
            return false;
        }
        return new $methodClass;
    }
}
