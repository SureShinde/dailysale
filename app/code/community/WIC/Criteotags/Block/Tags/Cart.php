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
 * @package		WIC_Luxroutage
 * @copyright   Copyright (c) 2010-2014 Web In Color (http://www.webincolor.fr)
 * @author		Web In Color <contact@webincolor.fr>
 * */
class WIC_Criteotags_Block_Tags_Cart extends Mage_Core_Block_Abstract {

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

            $html .= '{event: "viewBasket", ';
            $html .= 'product: [ ';

            foreach ($this->getCartItems() as $_item) {

                $html .= '{ id: "' . $_item['id'] . '", price: ' . $_item['price'] . ', quantity: ' . $_item['qty'] . ' },';
            }

            $html .= ']}';
            $html .= ');';
            $html .='</script>';
        }

        return $html;
    }

    protected function getCartItems() {

        $quote = Mage::getSingleton('checkout/session')->getQuote();

        $_producttype = Mage::helper('criteotags')->getProductType();

        $items = array();

        foreach ($quote->getAllItems() as $item) {
            Mage::log($item->getProductId() . " : " . $item->getDiscountAmount());

            $info['id'] = $item->getProductId();
            $info['qty'] = (int) $item->getQty();
            $info['price'] = floatval(($item->getPrice() * $info['qty'] - $item->getDiscountAmount()) / $info['qty']);

            // Load Product to product Type
            $product = Mage::getModel('catalog/product')->load($item->getProductId());

            // Keep the parent price for child
            if ($product->getTypeId() == "grouped" || $product->getTypeId() == "configurable" || $product->getTypeId() == "bundle") {
                $parent['qty'] = (int) $item->getQty();
                $parent['price'] = floatval(($item->getPrice() * $parent['qty'] - $item->getDiscountAmount()) / $parent['qty']);
            } elseif ($item->getParentItemId() && isset($parent)) {
                $info['qty'] = $parent['qty'];
                $info['price'] = $parent['price'];
                unset($parent);
            }



            switch ($_producttype) {
                case 1 : // Child
                    if ($product->getTypeId() != "grouped" && $product->getTypeId() != "configurable" && $product->getTypeId() != "bundle") {

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
