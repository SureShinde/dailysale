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
 * @copyright  Copyright (c) 2013 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Customer Credit extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @author     MageWorx Dev Team
 */

class MageWorx_CustomerCredit_Model_Import_Credit extends MageWorx_CustomerCredit_Model_Import_Abstract
{
    /**
     * Change data
     * @param array $entity
     * @return boolean
     */
    protected function _changeData($entity = array()) {
        try {
            $website_code   = $entity[0];
            $customerEmail  = $entity[1];
            $creditValue    = $entity[2];
            $comment        = $entity[3];
            
            $website = Mage::getSingleton('core/website')->load($website_code,'code'); 
            $this->customerEmail = $customerEmail;
            $customer = Mage::getModel('customer/customer')->setWebsiteId($website->getWebsiteId())->loadByEmail($customerEmail);
            
            $customerCredit = Mage::getModel('customercredit/credit')->setCustomerId($customer->getId())->setWebsiteId($website->getWebsiteId())->setComment($comment)->loadCredit();
          
            $credit = $customerCredit->getValue();
           
            if(strpos($creditValue,'-')!==false) {
                $customerCredit->setValueChange($creditValue);
            }
            elseif(strpos($creditValue,'+')!==false) {
                $customerCredit->setValueChange($creditValue);
            }
            else {
                $customerCredit->setValueChange(0-$credit)->save();
                $customerCredit->setValueChange($creditValue);
            }
            $customerCredit->getLogModel()->setActionType(MageWorx_CustomerCredit_Model_Credit_Log::ACTION_TYPE_IMPORT)->setComment($comment);
            $customerCredit->save();
            $customer->setData('customer_credit_data',$customerCredit->getData());
            
            // if send email
            if (Mage::helper('customercredit')->isSendNotificationBalanceChanged()) {                
                Mage::helper('customercredit')->sendNotificationBalanceChangedEmail($customer);
            }
       } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return true;
    }
}
