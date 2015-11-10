<?php

class Unirgy_Rma_Block_Order_Comments extends Mage_Sales_Block_Order_Comments
{
    public function getComments()
    {
        if (is_null($this->_commentCollection)) {
            $entity = $this->getEntity();
            if ($entity instanceof Mage_Sales_Model_Order_Invoice) {
                $collectionClass = 'sales/order_invoice_comment_collection';
            } else if ($entity instanceof Mage_Sales_Model_Order_Creditmemo) {
                $collectionClass = 'sales/order_creditmemo_comment_collection';
            } else if ($entity instanceof Mage_Sales_Model_Order_Shipment) {
                $collectionClass = 'sales/order_shipment_comment_collection';
            } else if ($entity instanceof Unirgy_Rma_Model_Rma) {
                $collectionClass = 'urma/rma_comment_collection';
            } else {
                Mage::throwException(Mage::helper('udropship')->__('Invalid entity model'));
            }

            $this->_commentCollection = Mage::getResourceModel($collectionClass);
            $this->_commentCollection->setParentFilter($entity)
               ->setCreatedAtOrder()
               ->addVisibleOnFrontFilter();
        }

        return $this->_commentCollection;
    }
}