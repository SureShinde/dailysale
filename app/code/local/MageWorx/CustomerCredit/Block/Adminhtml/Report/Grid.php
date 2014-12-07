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
 
class MageWorx_CustomerCredit_Block_Adminhtml_Report_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
            parent::__construct();
            $this->setId('customercredit_report_grid');
            $this->setSaveParametersInSession(true);
            $this->setDefaultSort('log_id');
            $this->setUseAjax(true);
            $this->setDefaultDir('asc');
//            $this->setVarNameFilter('credit_report_filter');
       }
  
       protected function _toHtml() {
           $html = parent::_toHtml();
           if(!$this->getRequest()->getParam('isAjax')) {
           $html = str_replace('<div id="customercredit_report_grid">','<div id="customercredit_report_grid">'.$this->getStatistic()."<br>",$html);
           }
           else {
           $html = $this->getStatistic()."<br>".$html;
           }
           return $html;
       }

       private function getStatistic()
       {
           $totalCredits            = 0;
           $totalUsed               = 0;
           $customers               = array();
           
           $collection = Mage::getResourceModel('customercredit/credit_log_collection');
           $collectionTotal = Mage::getResourceModel('customercredit/credit_collection');
           $collectionTotal->getSelect()->where('value>0');
           foreach ($collectionTotal as $item) {
               if(!isset($customers[$item->getWebsiteId()])) {
                   $customers[$item->getWebsiteId()] = array();
               }
          
               $totalCredits += $item->getValue();
               if($item->getValue()>0) {
                   $customers[$item->getWebsiteId()][]=$item->getCustomerId();
               }
           }
           foreach ($collection as $item)
           {
               $value = $item->getValueChange();
               if(in_array($item->getActionType(),array(MageWorx_CustomerCredit_Model_Credit_Log::ACTION_TYPE_USED,MageWorx_CustomerCredit_Model_Credit_Log::ACTION_TYPE_CREDIT_PRODUCT))) {
                    $totalUsed += $value;
               }
           }
           
           $totalCredits    = $totalCredits;
           $totalUsed       = round(abs($totalUsed),2);
           $totalCustomers  = (int) Mage::getResourceModel('customer/customer_collection')->getSize();
           ksort($customers);
           $customerStatistic = array();
//           echo "<pre>"; print_r($customers); exit;
           foreach ($customers as $key=>$website) {
               if($key==0) {
                   $websiteName = $this->__("Global");
               } else {
                   $websiteName = Mage::app()->getWebsite($key)->getName();
               }
               $persent         = sizeof($website) / $totalCustomers *100;
               $customerStatistic[] = $websiteName .": ". sizeof($website) . " (".$this->__('%s of all customers',round($persent,2)."%").")";
           }
           return "<div id='customercredit'>
                    <div class='notification-global' style='padding-right:50px; padding-left:20px; border:1px solid #EEE2BE; background:#FFF9E9; width: 400px;'><span class='label'>".$this->__('Total credits in system')."</span>: <span style='float:right;'>$totalCredits</span></div>
                    <div class='notification-global' style='padding-right:50px; padding-left:20px; border:1px solid #EEE2BE; background:#FFF9E9; width: 400px;'><span class='label'>".$this->__('Total credits used')."</span>: <span style='float:right;'>$totalUsed</span></div>
                    <div class='notification-global' style='padding-right:50px; padding-left:20px; border:1px solid #EEE2BE; background:#FFF9E9; width: 400px;'><span class='label'>".$this->__('Customers with credits')."</span>: <span style='float:right;'>".join(",<br>",$customerStatistic)."</span></div>
                  </div>";
       }

       protected function _prepareColumns() {
        $this->addExportType('*/*/exportCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('reports')->__('Excel XML'));
        
        $helper = Mage::helper('customercredit');
        $this->addColumn('log_id', array(
            'header'    => $helper->__('Log Id'),
            'index'     => 'log_id',
            'type'      => 'int',
            'width'     => '50px',
            'totals_label'  => Mage::helper('sales')->__('Total'),
        ));
        
        $this->addColumn('customer_id', array(
            'header'    => $helper->__('Customer Id'),
            'index'     => 'customer_id',
            'type'      => 'int',
            'width'     => '50px',
        ));
        
        $this->addColumn('customer_name', array(
            'header'    => $helper->__('Customer Name'),
            'index'     => 'customer_name',
            'type'      => 'text',
            'nl2br'     => true,
       //     'sortable'  => false,
            'filter'    => false,
        ));
        
        $this->addColumn('email', array(
            'header'    => $helper->__('Customer Email'),
            'index'     => 'email',
            'type'      => 'text',
            'nl2br'     => true,
        ));
        
        $groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt'=> 0))
            ->load()
            ->toOptionHash();
        
        $this->addColumn('group_id', array(
            'header'    =>  $helper->__('Customer Group'),
            'width'     =>  '100',
            'index'     =>  'group_id',
            'type'      =>  'options',
            'options'   =>  $groups,
        ));
        
        $this->addColumn('value_change', array(
            'header'    => $helper->__('Added/Deducted'),
            'index'     => 'value_change',
            'width'     => '50px',
            'renderer'  => 'customercredit/adminhtml_widget_grid_column_renderer_currencychange'
        ));
        
        $this->addColumn('credit_balance', array(
            'header'        => $helper->__('Credit Balance'),
            'index'         => 'credit_balance',
            'type'          => 'int',
            'filter_index'  => 'main_table.value',
            'width'         => '50px',
            'renderer'      => 'customercredit/adminhtml_widget_grid_column_renderer_currency'
        ));       
        
        $this->addColumn('action_date', array(
            'header'   => $helper->__('Modified On'),
            'index'    => 'action_date',
            'type'     => 'datetime',
            'width'    => '150px',
        //    'filter'   => false,
        ));
        
        $this->addColumn('staff_name', array(
            'header'   => $helper->__('Modified By'),
            'index'    => 'staff_name',
            'type'     => 'text',
            'width'    => '150px',
        //    'filter'   => false,
        ));
        
        $websites = Mage::getResourceModel('core/website_collection')
            ->addFieldToFilter('website_id', array('gt'=> 0))
            ->load()
            ->toOptionHash();
        
        $this->addColumn('website_id', array(
            'header'        =>  $helper->__('Website'),
            'width'         =>  '100',
            'index'         =>  'website_id',
            'filter_index'  => 'credit.website_id',
            'type'          =>  'options',
            'options'       =>  $websites,
        ));
        
        $this->addColumn('comment', array(
            'header'    => $helper->__('Comment'),
            'index'     => 'comment',
            'type'      => 'text',
            'nl2br'     => true,
            'sortable'  => false,
            'filter'   => false,
        ));
        
        $this->addColumn('action_type', array(
            'header'    => $helper->__('Action Type'),
            'width'     => '50px',
            'index'     => 'action_type',
            'sortable'  => false,
            'type'      => 'options',
            'options'   => Mage::getSingleton('customercredit/credit_log')->getActionTypesOptions(),
        ));
        
        $this->addColumn('action',
            array(
                'header'    => $helper->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getCustomerId',
                'actions'   => array(
                    array(
                        'caption' => $helper->__('Edit'),
                        'url'     => array(
                            'base'=>'adminhtml/customer/edit',
                        ),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
        ));
        
        
        return parent::_prepareColumns();
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('customercredit/credit_log_collection')
                ->addCustomerToSelect();
        //$collection->getSelect()->order('main_table.log_id DESC');
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }
    
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/customer/edit', array(
            'id'=>$row->getCustomerId())
        );
    }
    
    /**
     * Retrieve grid as CSV
     *
     * @return unknown
     */
    public function getCsv()
    {
        $csv = '';
        $this->_isExport = true;
        $this->_prepareGrid();
        $this->getCollection()->getSelect()->limit();
        $this->getCollection()->setPageSize(0);
        $this->getCollection()->load();
        $this->_afterLoadCollection();

        $data = array();
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem()) {
                $data[] = '"'.$column->getExportHeader().'"';
            }
        }
          array_pop($data);                                     // Remove Action Title
        $csv.= implode(',', $data)."\n";

        foreach ($this->getCollection() as $item) {
            $data = array();
            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $data[] = '"' . str_replace(array('"', '\\'), array('""', '\\\\'),
                        $column->getRowFieldExport($item)) . '"';
                }
            }
              array_pop($data);                                     // Remove Action Data
            $csv.= implode(',', $data)."\n";
        }

        if ($this->getCountTotals())
        {
            $data = array();
            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $data[] = '"' . str_replace(array('"', '\\'), array('""', '\\\\'),
                        $column->getRowFieldExport($this->getTotals())) . '"';
                }
            }
            $csv.= implode(',', $data)."\n";
        }
       
        return $csv;
    }

    /**
     * Retrieve grid as Excel Xml
     *
     * @return unknown
     */
    public function getExcel($filename = '')
    {
        $this->_prepareCollection();
        $this->_prepareColumns();

        $data = array();
        $row = array($this->__('Period'));
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem()) {
                $row[] = $column->getHeader();
            }
        }
        $data[] = $row;

        foreach ($this->getCollection() as $_index=>$_item) {
            $row = array($_index);
            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $row[] = $_item->getData($column->getId());
                }
            }
            $data[] = $row;
        }
        $xmlObj = new Varien_Convert_Parser_Xml_Excel();
        $xmlObj->setVar('single_sheet', $filename);
        $xmlObj->setData($data);
        $xmlObj->unparse();

        return $xmlObj->getData();
    }
}