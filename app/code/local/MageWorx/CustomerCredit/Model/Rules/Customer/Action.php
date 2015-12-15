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
class MageWorx_CustomerCredit_Model_Rules_Customer_Action extends Mage_Rule_Model_Rule
{
    const MAGEWORX_CUSTOMER_ACTION_TAG          = 1;
    const MAGEWORX_CUSTOMER_ACTION_REVIEW       = 2;
    const MAGEWORX_CUSTOMER_ACTION_FBLIKE       = 3;
    const MAGEWORX_CUSTOMER_ACTION_SUBSCRIBE    = 4;
    const MAGEWORX_CUSTOMER_ACTION_DOB          = 5;
    const MAGEWORX_CUSTOMER_ACTION_PLACEORDER   = 6;
    const MAGEWORX_CUSTOMER_ACTION_REGISTRATION = 7;
    const MAGEWORX_CUSTOMER_ORDER_COMPLETE      = 8;

    protected function _construct() {
        parent::_construct();
        $this->_init('customercredit/rules_customer_action');
        $this->setIdFieldName('id');
    }
    
    public function getActionTypesOptions() {
        return array(
            self::MAGEWORX_CUSTOMER_ACTION_TAG          => Mage::helper('customercredit')->__('Tagged Items'),
            self::MAGEWORX_CUSTOMER_ACTION_REVIEW       => Mage::helper('customercredit')->__('Reviewed Items'),
            self::MAGEWORX_CUSTOMER_ACTION_FBLIKE       => Mage::helper('customercredit')->__('Facebook like'),
            self::MAGEWORX_CUSTOMER_ACTION_SUBSCRIBE    => Mage::helper('customercredit')->__('Customer Subscribe to newsletter'),
            self::MAGEWORX_CUSTOMER_ACTION_DOB          => Mage::helper('customercredit')->__('Customer BDay'),
            self::MAGEWORX_CUSTOMER_ACTION_PLACEORDER   => Mage::helper('customercredit')->__('Customer Place Order'),
            self::MAGEWORX_CUSTOMER_ACTION_REGISTRATION => Mage::helper('customercredit')->__('Customer Registered in Site'),
            self::MAGEWORX_CUSTOMER_ORDER_COMPLETE      => Mage::helper('customercredit')->__("Customer's order complete"),
        );
    }
     
    public function loadByRuleAndCustomer($ruleId, $customerId) {
	$this->getResource()->loadByRuleAndCustomer($this, $ruleId, $customerId);
        return $this;
    }
}