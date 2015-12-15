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
 
class MageWorx_CustomerCredit_Adminhtml_ReportController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction() {
        $this->loadLayout()
            ->_setActiveMenu('report');
      $this->_title($this->__('Reports'))->_title($this->__('Customer Credit Report'));
        $this->_addContent($this->getLayout()->createBlock('customercredit/adminhtml_report'));
        $this->renderLayout();
    }
    
    public function gridAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function exportCsvAction() {
        $fileName   = 'custmer_credit.csv';
        $content    = $this->getLayout()->createBlock('customercredit/adminhtml_report_grid')
            ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export new accounts report grid to Excel XML format
     */
    public function exportExcelAction() {
        $fileName   = 'custmer_credit.xml';
        $content    = $this->getLayout()->createBlock('customercredit/adminhtml_report_grid')
            ->getExcel($fileName);

        $this->_prepareDownloadResponse($fileName, $content);
    }
        
}