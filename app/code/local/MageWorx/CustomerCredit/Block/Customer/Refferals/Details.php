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

class MageWorx_CustomerCredit_Block_Customer_Refferals_Details extends MageWorx_CustomerCredit_Block_Customer_View_Credit
{
    const CC_MIN_CREDIT_CODE    = 1;
    const CC_MAX_CREDIT_CODE    = 1000;
    const CC_DEFAULT_QTY        = 5;
    const CC_MAX_QTY            = 100;
    
    public function __construct() {
        parent::__construct();
        $customer = Mage::getModel('customer/session')->getCustomer();
        $collection = Mage::getResourceModel('customercredit/code_collection')->addOwnerFilter($customer->getId());
        $this->setCollection($collection);
    }
    
    public function minCreditCode()
    {
        return self::CC_MIN_CREDIT_CODE;
    }
    
    public function defaultCodes()
    {
        return self::CC_DEFAULT_QTY;
    }

    protected function _prepareLayout() {
        parent::_prepareLayout();
        $customer = Mage::getModel('customer/session')->getCustomer();
        $pager = $this->getLayout()->createBlock('page/html_pager', 'customercredit.credit.code.pager')->setCollection($this->getCollection());
        $this->setChild('pager', $pager);
        $this->getCollection()->load();
        return $this;
    }

    public function getPagerHtml() {
        return $this->getChildHtml('pager');
    }

    public function getMaxNumberCode()
    {
        $customer = Mage::getModel('customer/session')->getCustomer();
        $credit = Mage::getModel('customercredit/credit')->setCustomerId($customer->getId())->loadCredit();
        $creditValue = $credit->getValue();
    }
}