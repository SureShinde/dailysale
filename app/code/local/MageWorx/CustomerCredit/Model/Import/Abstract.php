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

class MageWorx_CustomerCredit_Model_Import_Abstract extends Mage_ImportExport_Model_Import
{
    public  $errors = array();
    public  $totalRecords = 0;
    public  $currentInc = 0;
    public  $customerEmail;
    private $_fileContent;
    
    /**
     * Init import
     */
    private function _init() {
        $content = Mage::getSingleton('admin/session')->getCustomerCreditImportFileContent();
        $this->_fileContent = $content;
        $this->totalRecords = sizeof($this->_fileContent);
    }
    
    /**
     * Run import
     * @return MageWorx_CustomerCredit_Model_Import_Abstract
     */
    public function run() {
        $this->_init();
        $content = $this->_fileContent;
        $currentValueId = Mage::app()->getRequest()->getParam('next',1);
        $this->currentInc = $currentValueId;
        $this->_changeData($content[$currentValueId-1]);
        return $this;
    }
}
