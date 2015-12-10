<?php

class Unirgy_Rma_Block_Order_Print_Rma extends Mage_Sales_Block_Items_Abstract
{
    protected $_rmasCollection;

    protected function _beforeToHtml()
    {
        $rma = Mage::registry('current_rma');
        if($rma) {
            $this->_rmasCollection = array($rma);
        } else {
            Mage::helper('urma')->initOrderRmasCollection($this->getOrder());
            $this->_rmasCollection = $this->getOrder()->getRmasCollection();
        }

        return parent::_beforeToHtml();
    }

    protected function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle($this->__('Order # %s', $this->getOrder()->getRealOrderId()));
        }
        $this->setChild(
            'payment_info',
            $this->helper('payment')->getInfoBlock($this->getOrder()->getPayment())
        );
    }

    public function getBackUrl()
    {
        return Mage::getUrl('*/*/history');
    }

    public function getPrintUrl()
    {
        return Mage::getUrl('*/*/print');
    }

    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }

    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function getRma()
    {
        return Mage::registry('current_rma');
    }

    protected function _prepareItem(Mage_Core_Block_Abstract $renderer)
    {
        $renderer->setPrintStatus(true);

        return parent::_prepareItem($renderer);
    }

    public function getRmasCollection()
    {
        return $this->_rmasCollection;
    }

    public function getRmaAddressFormattedHtml($rma)
    {
        $shippingAddress = $rma->getShippingAddress();
        if(!($shippingAddress instanceof Mage_Sales_Model_Order_Address)) {
            return '';
        }
        return $shippingAddress->format('html');
    }

    /**
     * Getter for billing address of order by format
     *
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    public function getBillingAddressFormattedHtml($order)
    {
        $billingAddress = $order->getBillingAddress();
        if(!($billingAddress instanceof Mage_Sales_Model_Order_Address)) {
            return '';
        }
        return $billingAddress->format('html');
    }

    public function getRmaItems($rma)
    {
        $res = array();
        foreach ($rma->getItemsCollection() as $item) {
            if (!$item->getOrderItem()->getParentItem()) {
                $res[] = $item;
            }
        }
        return $res;
    }
}

