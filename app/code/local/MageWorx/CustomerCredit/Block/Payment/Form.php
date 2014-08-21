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
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @copyright  Copyright (c) 2010 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */
/**
 * Customer Credit extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */
class MageWorx_CustomerCredit_Block_Payment_Form extends Mage_Payment_Block_Form {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('customercredit/payment/form.phtml');
    }

    public function getCreditValue() {
        if((Mage::helper('customercredit')->getRealCreditValue()!=Mage::helper('customercredit')->getUsedCreditValue()) ||
            Mage::helper('customercredit')->getRealCreditValue()<Mage::getSingleton('checkout/cart')->getQuote()->getSubtotal()) {
               return Mage::helper('customercredit')->getRealCreditValue();
        }
        else {
             return Mage::getSingleton('checkout/cart')->getQuote()->getSubtotal();
        }
    }
}