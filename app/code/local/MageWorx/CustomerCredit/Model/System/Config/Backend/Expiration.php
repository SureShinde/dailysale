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
 * @copyright  Copyright (c) 2013 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */
 
/**
 * Customer Credit extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */

class MageWorx_CustomerCredit_Model_System_Config_Backend_Expiration extends Mage_Core_Model_Config_Data
{    
    
    protected function _afterSave() {
        parent::_afterSave();
        if(($this->getValue()!=$this->getOldValue()) && Mage::getStoreConfig('mageworx_customers/customercredit_expiration/expiration_enable')) {
            $model = Mage::getModel('cron/schedule');
            $model->setJobCode('credit_expiration_date_refresh')
                  ->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
                  ->setCreatedAt(now())
                  ->setScheduledAt(date("Y-m-d h:i:s",time()+60));
            $model->save();
        }
    }
}