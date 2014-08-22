<?php
/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @copyright  Copyright (c) 2012 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Customer Credit extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @author     MageWorx Dev Team
 */

class MageWorx_CustomerCredit_Block_Checkout_Cart extends Mage_Checkout_Block_Cart {

    protected function _toHtml() {
        $html = parent::_toHtml();
        $block = $this->getLayout()->addBlock("customercredit/checkout_cart_credit","checkout.cart.credit");
        $block->setTemplate("customercredit/checkout/cart_credit.phtml");
        $html = str_replace('div class="col-2">', 'div class="col-2">'.$block->toHtml(), $html);
        return $html;
    }
}