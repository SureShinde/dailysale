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

class Unirgy_DropshipPo_Model_Po extends Mage_Sales_Model_Abstract
{
    protected $_items;
    protected $_order;
    protected $_comments;
    protected $_vendorComments;
    protected $_shipments;
    protected $_invoices;
    
    protected $_eventPrefix = 'udpo_po';
    protected $_eventObject = 'po';
    
    protected $_commentsChanged = false;

    protected function _construct()
    {
        $this->_init('udpo/po');
    }

    public function loadByIncrementId($incrementId)
    {
        $ids = $this->getCollection()
            ->addAttributeToFilter('increment_id', $incrementId)
            ->getAllIds();

        if (!empty($ids)) {
            reset($ids);
            $this->load(current($ids));
        }
        return $this;
    }


    /**
     * Declare order for shipment
     *
     * @param   Mage_Sales_Model_Order $order
     * @return  Mage_Sales_Model_Order_Shipment
     */
    public function setOrder(Mage_Sales_Model_Order $order)
    {
        $this->_order = $order;
        $this->setOrderId($order->getId())
            ->setStoreId($order->getStoreId());
        return $this;
    }


    /**
     * Retrieve hash code of current order
     *
     * @return string
     */
    public function getProtectCode()
    {
        return (string)$this->getOrder()->getProtectCode();
    }

    /**
     * Retrieve the order the shipment for created for
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order instanceof Mage_Sales_Model_Order) {
            $this->_order = Mage::getModel('sales/order')->load($this->getOrderId());
        }
        return $this->_order;
    }

    protected $_stockPo;
    public function getStockPo()
    {
        if (Mage::helper('udropship')->isModuleActive('ustockpo')) {
            if (null === $this->_stockPo && $this->getUstockpoId()) {
                $this->_stockPo = Mage::getModel('ustockpo/po')->load($this->getUstockpoId());
            }
        }
        return $this->_stockPo;
    }
    public function setStockPo($stockPo)
    {
        $this->_stockPo = $stockPo;
        return $this;
    }

    /**
     * Retrieve billing address
     *
     * @return Mage_Sales_Model_Order_Address
     */
    public function getBillingAddress()
    {
        return $this->getOrder()->getBillingAddress();
    }

    /**
     * Retrieve shipping address
     *
     * @return Mage_Sales_Model_Order_Address
     */
    public function getShippingAddress()
    {
        return $this->getOrder()->getShippingAddress();
    }

    public function register()
    {
        return $this;
    }
    
    public function cancel()
    {
        foreach ($this->getAllItems() as $item) {
            $item->cancel();
        }
        Mage::dispatchEvent('udpo_po_cancel', array('order'=>$this->getOrder(), 'udpo'=>$this));
        return $this;
    }
    
    public function hasShippedItem()
    {
        foreach ($this->getAllItems() as $item) {
            if ($item->getQtyShipped() > 0) {
                return true;
            }
        }
    }
    
    public function hasCanceledItem()
    {
        foreach ($this->getAllItems() as $item) {
            if ($item->getQtyCanceled() > 0) {
                return true;
            }
        }
    }
    
    public function hasItemToShip()
    {
        foreach ($this->getAllItems() as $item) {
            if ($item->getQtyToShip() > 0 && !$item->getOrderItem()->getIsVirtual()) {
                return true;
            }
        }
    }

    public function getItemsCollection()
    {
        if (empty($this->_items)) {
            $this->_items = Mage::getResourceModel('udpo/po_item_collection')
                ->setPoFilter($this->getId());

            if ($this->getId()) {
                foreach ($this->_items as $item) {
                    $item->setPo($this);
                }
            }
        }
        return $this->_items;
    }

    public function getAllItems()
    {
        $items = array();
        foreach ($this->getItemsCollection() as $item) {
            if (!$item->isDeleted()) {
                $items[] =  $item;
            }
        }
        return $items;
    }

    public function getItemById($itemId)
    {
        foreach ($this->getItemsCollection() as $item) {
            if ($item->getId()==$itemId) {
                return $item;
            }
        }
        return false;
    }

    public function addItem(Unirgy_DropshipPo_Model_Po_Item $item)
    {
        $item->setPo($this)
            ->setParentId($this->getId())
            ->setStoreId($this->getStoreId());
        if (!$item->getId()) {
            $this->getItemsCollection()->addItem($item);
        }
        return $this;
    }
    
    public function getUdropshipStatusName($status=null)
    {
        if (is_null($status)) {
            $status = $this->getUdropshipStatus();
        }
        $statuses = Mage::getSingleton('udpo/source')->setPath('po_statuses')->toOptionHash();
        return isset($statuses[$status]) ? $statuses[$status] : (in_array($status, $statuses) ? $status : 'Unknown');
    }
    
    public function addComment($comment, $isVendorNotified=false, $visibleToVendor=false)
    {
        $this->_commentsChanged = true;
        if (!($comment instanceof Unirgy_DropshipPo_Model_Po_Comment)) {
            $comment = Mage::getModel('udpo/po_comment')
                ->setComment($comment)
                ->setIsVendorNotified($isVendorNotified)
                ->setIsVisibleToVendor($visibleToVendor)
                ->setUdropshipStatus($this->getUdropshipStatusName());
        }
        if ($this->getUseCommentUsername()) {
            $comment->setUsername($this->getUseCommentUsername());
        }
        $comment->setPo($this)
            ->setParentId($this->getId())
            ->setStoreId($this->getStoreId());
        if (!$comment->getId()) {
            $this->getOrder()->addStatusHistoryComment(Mage::helper('udpo')->__("Purchase Order # %s: (%s)\n%s", 
                $this->getIncrementId(), $this->getUdropshipStatusName(), $comment->getComment()
            ));
            $this->getCommentsCollection()->addItem($comment);
        }
        return $this;
    }

    public function getCommentsCollection($reload=false)
    {
        if (is_null($this->_comments) || $reload) {
            $this->_comments = Mage::getResourceModel('udpo/po_comment_collection')
                ->setPoFilter($this->getId())
                ->setCreatedAtOrder();

            /**
             * When shipment created with adding comment, comments collection must be loaded before we added this comment.
             */
            $this->_comments->load();

            if ($this->getId()) {
                foreach ($this->_comments as $comment) {
                    $comment->setPo($this);
                }
            }
        }
        return $this->_comments;
    }
    
    public function getVendorCommentsCollection($reload=false)
    {
        if (is_null($this->_vendorComments) || $reload) {
            $this->_vendorComments = Mage::getResourceModel('udpo/po_comment_collection')
                ->setPoFilter($this->getId())
                ->addFieldToFilter('is_visible_to_vendor', 1)
                ->setCreatedAtOrder();

            /**
             * When shipment created with adding comment, comments collection must be loaded before we added this comment.
             */
            $this->_vendorComments->load();

            if ($this->getId()) {
                foreach ($this->_vendorComments as $comment) {
                    $comment->setPo($this);
                }
            }
        }
        return $this->_vendorComments;
    }
    
    public function canCreateShipment()
    {
        //$enableVirtual = Mage::getStoreConfig('udropship/misc/enable_virtual', $this->getStoreId());
        foreach ($this->getAllItems() as $item) {
            if ($item->getQtyToShip()>0 && !$item->getIsVirtual()) {
                return true;
            }
        }
        return false;
    }
    
	public function canCreateInvoice()
    {
    	$canFlag = false;
        foreach ($this->getAllItems() as $item) {
            $oItemIds[] = $item->getOrderItemId();
            if ($item->getQtyToInvoice()>0) {
                $canFlag = true;
            }
        }
        return $canFlag && !$this->getResource()->hasExternalInvoice($this, $oItemIds);
    }
    
    public function canInvoiceShipment($shipment)
    {
        if ($this->getId()!=$shipment->getUdpoId()) {
            return false;
        }
        $shipInvoiceMatch = true;
        $oItemIds = array();
        foreach ($shipment->getAllItems() as $sItem) {
            $oItem = $sItem->getOrderItem();
            $oItemIds[] = $oItem->getId();
            if ($oItem->isDummy(true) != $oItem->isDummy()) {
                return false;
            }
            if (!($poItem = $this->getItemById($sItem->getUdpoItemId()))) {
                return false;
            }
            if ($poItem->getQtyToInvoice()<$sItem->getQtyShipped()) {
                return false;
            }
        }
        $hasExternalInvoice = false;
        foreach ($this->getOrder()->getInvoiceCollection() as $oInvoice) {
            if ($oInvoice->getUdpoId() != $this->getId()) {
                foreach ($oInvoice->getAllItems() as $iItem) {
                    if (in_array($iItem->getOrderItemId(), $oItemIds)) {
                        $hasExternalInvoice = true;
                    }
                }
            }
        }
        return !$hasExternalInvoice
            && !$this->getResource()->hasExternalInvoice($this, $oItemIds)
            && !$this->getOrder()->getInvoiceCollection()->getItemByColumnValue('shipment_id', $shipment->getId());        
    }
    
    public function canCancel()
    {
        foreach ($this->getAllItems() as $item) {
            if ($item->getQtyToCancel()>0) {
                return true;
            }
        }
        return false;
    }
    
    public function isShipmentsShipped($all=true)
    {
        if ($this->getIsVirtual()) return true;
        $shipped = false;
        foreach ($this->getShipmentsCollection() as $shipment) {
            if ($shipment->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED) {
                continue;
            }
            if ($shipment->getUdropshipStatus()!=Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_SHIPPED
                && $shipment->getUdropshipStatus()!=Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED
                && $all
            ) {
                return false;
            } elseif ($shipment->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_SHIPPED
                || $shipment->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED
            ) {
                $shipped = true;
            }
        }
        return $shipped;
    }
    
    public function isShipmentsDelivered()
    {
        if ($this->getIsVirtual()) return true;
        $delivered = false;
        foreach ($this->getShipmentsCollection() as $shipment) {
            if ($shipment->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED) {
                continue;
            }
            if ($shipment->getUdropshipStatus()!=Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED) {
                return false;
            } elseif ($shipment->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED) {
                $delivered = true;
            }
        }
        return $delivered;
    }

    /**
     * Before object save
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    protected function _beforeSave()
    {
        if ((!$this->getId() || null !== $this->_items) && !count($this->getAllItems())) {
            Mage::throwException(
                Mage::helper('sales')->__('Cannot create an empty purchase order.')
            );
        }

        if (!$this->getOrderId() && $this->getOrder()) {
            $this->setOrderId($this->getOrder()->getId());
            $this->setShippingAddressId($this->getOrder()->getShippingAddress()->getId());
        }
        if (!$this->getUstockpoId() && $this->getStockPo()) {
            $this->setUstockpoId($this->getStockPo()->getId());
        }

        return parent::_beforeSave();
    }

    protected function _beforeDelete()
    {
        $this->_protectFromNonAdmin();
        return parent::_beforeDelete();
    }
    
    public function getShipmentsCollection($forceReload=false)
    {
        if (is_null($this->_shipments) || $forceReload) {
            if ($this->getId()) {
                $this->_shipments = Mage::getResourceModel('sales/order_shipment_collection')
                    ->addAttributeToFilter('udpo_id', $this->getId())
                    ->load();
                foreach ($this->_shipments as $s) {
                    $s->setUdpo($this);
                }
            } else {
                return false;
            }
        }
        return $this->_shipments;
    }
    
    public function getBaseShippingAmountLeft()
    {
        $usedBaseSa = 0;
        foreach ($this->getShipmentsCollection() as $_s) {
        	if ($_s->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED) {
                continue;
            }
            $usedBaseSa += $_s->getBaseShippingAmount();
        }
        return max(0,$this->getBaseShippingAmount()-$usedBaseSa);
    }
    
    public function getShippingAmountLeft()
    {
        $usedSa = 0;
        foreach ($this->getShipmentsCollection() as $_s) {
        	if ($_s->getUdropshipStatus()==Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_CANCELED) {
                continue;
            }
            $usedSa += $_s->getShippingAmount();
        }
        return max(0,$this->getShippingAmount()-$usedSa);
    }
    
	public function getRemainingWeight()
    {
        $weight = 0;
        foreach ($this->getAllItems() as $item) {
            $weight += $item->getWeight()*$item->getQtyToShip();
        }
        return $weight;
    }
    
    public function getRemainingValue()
    {
        $value = 0;
        foreach ($this->getAllItems() as $item) {
            $value += $item->getPrice()*$item->getQtyToShip();
        }
        return $value;
    }
    
    public function getInvoicesCollection($forceReload=false)
    {
        if (is_null($this->_invoices) || $forceReload) {
            if ($this->getId()) {
                $this->_invoices = Mage::getResourceModel('sales/order_invoice_collection')
                    ->addAttributeToFilter('udpo_id', $this->getId())
                    ->load();
                foreach ($this->_invoices as $i) {
                    $i->setUdpo($this);
                }
            } else {
                return false;
            }
        }
        return $this->_invoices;
    }

    protected function _afterSave()
    {
        if (null !== $this->_items) {
            foreach ($this->_items as $item) {
                $item->save();
            }
        }

        $this->saveComments();

        return parent::_afterSave();
    }
    
    public function saveComments()
    {
        if ($this->_commentsChanged) {
            $this->getCommentsCollection()->save();
            $this->getOrder()->getStatusHistoryCollection()->save();
        }
        return $this;
    }

    public function getStore()
    {
        return $this->getOrder()->getStore();
    }
    
    public function getVendorName()
    {
        return $this->getVendor()->getVendorName();
    }
    
    public function getVendor()
    {
        return Mage::helper('udropship')->getVendor($this->getUdropshipVendor());
    }

    public function getStockVendorName()
    {
        return $this->getStockVendor()->getVendorName();
    }

    public function getStockVendor()
    {
        return Mage::helper('udropship')->getVendor($this->getUstockVendor());
    }
}
