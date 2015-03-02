<?php

/**
 * Web In Color
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file WIC-LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://store.webincolor.fr/WIC-LICENSE.txt
 * 
 * @package		WIC_Criteotags
 * @copyright   Copyright (c) 2010-2014 Web In Color (http://www.webincolor.fr)
 * @author		Web In Color <contact@webincolor.fr>
 * */
class WIC_Criteotags_Block_Tags_Success extends Mage_Core_Block_Abstract {

    protected function _toHtml() {
        $html = '';

        if (Mage::helper('criteotags')->isTagsEnabled()) {
            $html .= '<script type="text/javascript">';
            $html .= 'window.criteo_q = window.criteo_q || [];';
            $html .= 'window.criteo_q.push(';
            $html .= '{ event: "setAccount", account: ' . Mage::helper('criteotags')->getAccountId() . '},';
            $html .= '{ event: "setSiteType", type: "' . Mage::helper('criteotags')->getSitetype() . '"},';

            if (Mage::helper('criteotags')->getCustomerId()) {
                $html .= '{ event: "setCustomerId", id: ' . Mage::helper('criteotags')->getCustomerId() . '},';
                $html .= '{ event: "setHashedEmail", email: ["' . Mage::helper('criteotags')->getHashedEmail() . '"] },';
            }

            $html .= '{event: "trackTransaction" , id: "' . $this->getTransactionId() . '", new_customer: ' . (int) $this->isFirstPurchase() . ' ,';
            $html .= 'product: [ ';

            foreach ($this->getOrderItems() as $_item) {

                $html .= '{ id: "' . $_item['id'] . '", price: ' . $_item['price'] . ', quantity: ' . $_item['qty'] . ' },';
            }

            $html .= ']}';
            $html .= ');';
            $html .='</script>';
        }

        return $html;
    }

    protected function isFirstPurchase() {
        $orderCollection = Mage::getModel('sales/order')->getCollection();

        $orders = $orderCollection
                ->addAttributeToFilter("customer_id", array(Mage::helper('criteotags')->getCustomerId()))
                ->addAttributeToFilter('state', array('complete'));

        if ($orders->getSize() > 0) {
            return false;
        } else {
            return true;
        }
    }

    protected function getTransactionId() {
        return Mage::getSingleton('checkout/session')->getLastRealOrderId();
    }

    protected function getOrderItems() {

        $order = Mage::getSingleton('sales/order')->loadByIncrementId($this->getTransactionId());

        $_producttype = Mage::helper('criteotags')->getProductType();

        $items = array();

        foreach ($order->getAllItems() as $item) {

            $info['id'] = $item->getProductId();
            $info['qty'] = (int) $item->getQtyOrdered();
            $info['price'] = floatval($item->getPrice());

            // Load Product to product Type
            $product = Mage::getModel('catalog/product')->load($item->getProductId());

            // Keep the parent price for child
            if ($product->isGrouped() || $product->isConfigurable()) {
                $parent['price'] = floatval($item->getPrice());
                $parent['qty'] = (int) $item->getQtyOrdered();
            } elseif ($item->getParentItemId() && isset($parent)) {
                $info['price'] = $parent['price'];
                $info['qty'] = $parent['qty'];
                unset($parent);
            }



            switch ($_producttype) {
                case 1 : // Child
                    if (!$product->isGrouped() && !$product->isConfigurable()) {

                        $items[] = $info;
                    }

                    break;

                case 2 : // Parent
                    if (!$item->getParentItemId()) {

                        $items[] = $info;
                    }

                    break;

                default : // case 3 : // Child & Parent
                    $items[] = $info;

                    break;
            }
            unset($info);
        }
        return $items;
    }

}
