<?php

class Unirgy_DropshipPo_Model_SalesOrder extends Mage_Sales_Model_Order
{
    /*
    public function getUdpoNoSplitPoFlag()
    {
        return false;
    }
    */
    protected function _checkState()
    {
        if (!$this->getId()) {
            return $this;
        }

        $userNotification = $this->hasCustomerNoteNotify() ? $this->getCustomerNoteNotify() : null;

        if (!$this->isCanceled()
            && !$this->canUnhold()
            && !$this->canInvoice()
            && !$this->canShip()) {
            if (0 == $this->getBaseGrandTotal() || $this->canCreditmemo()) {
                if ($this->getState() !== self::STATE_COMPLETE) {
                    if (Mage::helper('udropship')->isUdropshipOrder($this)) {
                        $isComplete = true;
                        foreach ($this->getShipmentsCollection() as $shipment) {
                            if (!in_array($shipment->getUdropshipStatus(), array(
                                Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_SHIPPED,
                                Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED,
                                Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED
                            ))) {
                                $isComplete = false;
                                break;
                            }
                        }
                        if ($isComplete) {
                            $this->_setState(self::STATE_COMPLETE, true, '', $userNotification);
                        }
                    } else {
                        $this->_setState(self::STATE_COMPLETE, true, '', $userNotification);
                    }
                }
            }
            /**
             * Order can be closed just in case when we have refunded amount.
             * In case of "0" grand total order checking ForcedCanCreditmemo flag
             */
            elseif(floatval($this->getTotalRefunded()) || (!$this->getTotalRefunded() && $this->hasForcedCanCreditmemo())) {
                if ($this->getState() !== self::STATE_CLOSED) {
                    $this->_setState(self::STATE_CLOSED, true, '', $userNotification);
                }
            }
        }

        if ($this->getState() == self::STATE_NEW && $this->getIsInProcess()) {
            $this->setState(self::STATE_PROCESSING, true, '', $userNotification);
        }
        return $this;
    }
}
