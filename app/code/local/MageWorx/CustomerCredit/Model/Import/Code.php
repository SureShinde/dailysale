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

class MageWorx_CustomerCredit_Model_Import_Code extends MageWorx_CustomerCredit_Model_Import_Abstract
{
    public $code;
    
    /**
     * Change data
     * @param array $entity
     * @return boolean
     */
    protected function _changeData($entity = array()) {
        try {
            $code         = $entity[0];
            $credit_value = $entity[1];
            $website_code = $entity[2];
            $is_onetime   = $entity[3];
            $is_active    = $entity[4];
            $from_date    = $entity[5];
            $to_date      = $entity[6];
            
            $this->code = $code;
            
            $website = Mage::getSingleton('core/website')->load($website_code,'code'); 
            $rechargeCode = Mage::getModel('customercredit/code')->setIsNew(true);
            $rechargeCode->setCodeId(NULL)
                         ->setWebsiteId($website->getWebsiteId())
                         ->setCode($code)
                         ->setCredit($credit_value)
                         ->setCreatedDate(now())
                         ->setFromDate($from_date)
                         ->setToDate($to_date)
                         ->setIsActive($is_active)
                         ->setIsOnetime($is_onetime)
                         ->setComment($comment);
            $result = $rechargeCode->validateData($rechargeCode);
            if(is_array($result)) {
                $this->errors[] = Mage::helper('rule')->__('Code').' '.$code.' - '.$result[0];
            }
            $rechargeCode->save();
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return true;
    }
}
