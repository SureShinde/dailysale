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
 
class MageWorx_CustomerCredit_Adminhtml_CreditController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('customer/manage');
    }
    
    protected function _initCustomer($idFieldName = 'id') {
        $customerId = (int) $this->getRequest()->getParam($idFieldName);
        $customerModel = Mage::getModel('customer/customer');
        if ($customerId) {
            $customerModel->load($customerId);
        }
        if (!$customerModel->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('customercredit')->__('The customer not found'));
        }
        Mage::register('current_customer', $customerModel);
        return $this;
    }
    
    public function indexAction() {
        $this->_initCustomer();
        $this->loadLayout()
            ->renderLayout();
    }
    
    public function logGridAction() {
        $this->_initCustomer();
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('customercredit/adminhtml_customer_edit_tab_customercredit_log_grid')->toHtml()
        );
    }
    
    public function createProductAction() {        
        if (!Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore()->getId())->getIdBySku('customercredit')) {
            $productId = Mage::helper('customercredit')->createCreditProduct();

            if ($productId) {                                
                Mage::getModel('core/config_data')->load('mageworx_customers/customercredit_credit/credit_product', 'path')
                    ->setValue('customercredit')
                    ->setPath('mageworx_customers/customercredit_credit/credit_product')
                    ->save();                
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('customercredit')->__('Credit product was successfully created.'));
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('customercredit')->__('Failed to create credit product.'));
            }
        }    
        $this->_redirectReferer();
    }
    
    public function syncAction() {
        $syncType = $this->getRequest()->getParam('sync_type',MageWorx_CustomerCredit_Model_System_Config_Source_Sync::ACTION_TYPE_APPEND);
        $rewardPointCollection = Mage::getResourceModel('enterprise_reward/reward_collection');
        if(!$rewardPointCollection) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('customercredit')->__('Failed to sync credit balances. Please check Magento version.'));
            return $this->_redirectReferer();
        }
        $comment = "";
        foreach ($rewardPointCollection as $item) {
            $customerCredit = Mage::getModel('customercredit/credit');
            $customerCredit->setCustomerId($item->getCustomerId())
                    ->setWebsiteId($item->getWebsiteId())
                    ->setComment($comment)
                    ->loadCredit();
            if($syncType==MageWorx_CustomerCredit_Model_System_Config_Source_Sync::ACTION_TYPE_APPEND) {
                $customerCredit->setValueChange($item->getPointsBalance());
            } 
            if($syncType==MageWorx_CustomerCredit_Model_System_Config_Source_Sync::ACTION_TYPE_REPLACE) {
                $customerCredit->setValueChange(0 - floatval($customerCredit->getValue()) + floatval($item->getPointsBalance()));
            }
            $customerCredit->getLogModel()->setActionType(MageWorx_CustomerCredit_Model_Credit_Log::ACTION_TYPE_SYNC)->setComment($comment);
            $customerCredit->save();
        }
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('customercredit')->__('All data were synchronized.'));
        return $this->_redirectReferer();
    }
}