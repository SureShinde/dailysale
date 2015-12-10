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

class MageWorx_CustomerCredit_Model_Api extends Mage_Customer_Model_Api_Resource
{
    private $_customer = null;

    /**
     * Get Customer Object
     * @param int $id
     * @return Mage_Customer_Model_Customer
     */
    private function _getCustomer($id) {
        if (!$id) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        if(!$this->_customer) {
            try {
                $customer = Mage::getModel('customer/customer')->load($id);
            } catch (Mage_Core_Exception $e) {
                $this->_fault('not_exists', $e->getMessage());
            }
            $this->_customer = $customer;
        }
        return $this->_customer;
    }
    
    /**
     * Get Cedit list
     * @return array
     */
    public function listCredit() {
        $model = Mage::getModel('customercredit/credit')->setIsApi(true);
        $collection = $model->getCollection();
        $list = array();
        foreach($collection as $item) {
            $list[$item->getId()] = $item->getData();
        }
        return $list;
    }

    /**
     * Get credit by customer id
     * @param int $customerId
     * @return int|boolean
     */
    public function getCredit($customerId) {
        if($customer = $this->_getCustomer($customerId)) {
            try {
                $model = Mage::getModel('customercredit/credit')->setCustomerId($customerId)->setIsApi(true);
                $customerCredit = $model->loadCredit();
                return $customerCredit->getValue();
            } catch (Mage_Core_Exception $e) {
                $this->_fault('not_exists', $e->getMessage());
            }
        }
        return false;
    }
    
    /**
     * Set credit
     * @param int $customerId
     * @param float $value
     * @return boolean
     */
    public function setCredit($customerId,$value)
    {
        if($customer = $this->_getCustomer($customerId))
        {
            try {
                $customerCredit = Mage::getModel('customercredit/credit')->setCustomerId($customerId)->setIsApi(true)->loadCredit();
                $credit = $this->getCredit($customerId);
                $credit = 0-$credit;
                $customerCredit->setValueChange($credit)->save();
                $customerCredit->setValueChange($value)->save();
                return TRUE;
            } catch (Mage_Core_Exception $e) {
                $this->_fault('not_updated', $e->getMessage());
            }
            
        }
        return FALSE;
    }
    
    /**
     * Increase Credit Value
     * @param int $customerId
     * @param float $value
     * @return boolean
     */
    public function increaseCredit($customerId,$value) {
        if($customer = $this->_getCustomer($customerId)) {
            try {
                $customerCredit = Mage::getModel('customercredit/credit')->setCustomerId($customerId)->setIsApi(true)->loadCredit();
                $customerCredit->setValueChange($value)->save();
                return TRUE;
            } catch (Mage_Core_Exception $e) {
                $this->_fault('not_updated', $e->getMessage());
            }
            
        }
        return false;
    }
    
    /**
     * Decrease Credit value
     * @param int $customerId
     * @param float $value
     * @return boolean
     */
    public function decreaseCredit($customerId,$value) {
        if($customer = $this->_getCustomer($customerId)) {
            try {
                $value = 0 - $value;
                $customerCredit = Mage::getModel('customercredit/credit')->setCustomerId($customerId)->setIsApi(true)->loadCredit();
                $customerCredit->setValueChange($value)->save();
                return TRUE;
            } catch (Mage_Core_Exception $e) {
                $this->_fault('not_updated', $e->getMessage());
            }
            
        }
        return false;
    }
    
    /**
     * Generate new credit codes
     * @param float $credit_value
     * @param int $website_id
     * @param int $qty
     * @param date $from_date
     * @param date $to_date
     * @param boolean $is_active
     * @param int $code_length
     * @param int $group_length
     * @param string $group_separator
     * @param string $code_format
     * @return boolean
     */
    public function generateNewCodes($credit_value=1,$website_id=1,$qty=1,$from_date,$to_date,$is_active=true,$code_length=null,$group_length=null,$group_separator=null,$code_format=null) {
        if(!$code_length) $code_length=Mage::getStoreConfig('mageworx_customers/customercredit_recharge_codes/code_length');
        if(!$group_length) $group_length=Mage::getStoreConfig('mageworx_customers/customercredit_recharge_codes/group_length');
        if(!$group_separator) $group_separator=Mage::getStoreConfig('mageworx_customers/customercredit_recharge_codes/group_separator');
        if(!$code_format) $code_format=Mage::getStoreConfig('mageworx_customers/customercredit_recharge_codes/code_format');
        
        $codeModel = Mage::getModel('customercredit/code');
        $data = array('settings'=>array(),'details'=>array());
        
        $data['settings'] = array('code_length'=>$code_length,
                                  'group_length'=>$group_length,
                                  'group_separator'=>$group_separator,
                                  'code_format'=>$code_format,
                                  'qty'=>$qty);
        $data['details'] = array( 'credit' => $credit_value,
                                  'website_id' => $website_id,
                                  'is_active' => $is_active);
        $dataDetails = array();
        $dataDetails = $this->_filterDates($dataDetails, array($from_date, $to_date));
        $data['details'] = $dataDetails; 
        
        $codeModel->loadPost($data);
        $codeModel->generate();
        
        return true;
    }

}