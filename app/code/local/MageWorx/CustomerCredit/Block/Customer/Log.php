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

class MageWorx_CustomerCredit_Block_Customer_Log extends Mage_Core_Block_Template
{
    public function __construct() {
        parent::__construct();
        $this->setTemplate('customercredit/customer/log.phtml');
        $logCollection = Mage::getResourceModel('customercredit/credit_log_collection')
                ->addWebsiteFilter((Mage::helper('customercredit')->isScopePerWebsite()?(int)Mage::app()->getStore()->getWebsiteId():0))
                ->addCustomerFilter((int) Mage::getSingleton('customer/session')->getCustomerId())
                ->setOrder('action_date')
        ;
        $this->setLogItems($logCollection);
    }

    protected function _prepareLayout() {
        parent::_prepareLayout();
        $pager = $this->getLayout()->createBlock('page/html_pager', 'customercredit.credit.log.pager')->setCollection($this->getLogItems());
        $this->setChild('pager', $pager);
        $this->getLogItems()->load();
        return $this;
    }

    public function getPagerHtml() {
        return $this->getChildHtml('pager');
    }

    public function getActionTypeLabel($id) {
        $actionTypes = Mage::getSingleton('customercredit/credit_log')->getActionTypesOptions();
        if (isset($actionTypes[$id])) {
            return $actionTypes[$id];
        }
        return '';
    }
}