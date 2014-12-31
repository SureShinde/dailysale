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

class MageWorx_CustomerCredit_Block_Adminhtml_Customer_Edit_Tab_CustomerCredit_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct() {
        parent::__construct();
        $this->setId('creditLogGrid');
        $this->setDefaultSort('action_date');
        $this->setUseAjax(true);
    }

    public function getWebsiteOptions() {
        $options = Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash();        
        $options[0] = Mage::helper('customercredit')->__('Global');
        return $options;
    }    
    
    protected function _prepareColumns() {
        $helper = Mage::helper('customercredit');
        $this->addColumn('value', array(
            'header'    => $helper->__('Credit Balance'),
            'index'     => 'value',
            'sortable'  => false,
            'filter'    => false,
            'width'     => '50px',
            'renderer'  => 'customercredit/adminhtml_widget_grid_column_renderer_currency'
        ));
        $this->addColumn('value_change', array(
            'header'    => $helper->__('Added/Deducted'),
            'index'     => 'value_change',
            'sortable'  => false,
            'filter'    => false,
            'width'     => '50px',
            'renderer'  => 'customercredit/adminhtml_widget_grid_column_renderer_currencychange'
        ));
        $this->addColumn('website_id', array(
            'header'    => $helper->__('Website'),
            'index'     => 'website_id',
            'type'      => 'options',
            'options'   => $this->getWebsiteOptions(),
            'sortable'  => false,
            'width'     => '120px',
            'filter_index'=>'main_table.website_id',
            'align'      => 'center'
        ));
        $this->addColumn('action_date', array(
            'header'   => $helper->__('Modified On'),
            'index'    => 'action_date',
            'type'     => 'datetime',
            'width'    => '160px',
            'filter'   => false,
        ));
        $this->addColumn('action_type', array(
            'header'    => $helper->__('Action'),
            'width'     => '70px',
            'index'     => 'action_type',
            'sortable'  => false,
            'type'      => 'options',
            'options'   => Mage::getSingleton('customercredit/credit_log')->getActionTypesOptions(),
        ));
        $this->addColumn('comment', array(
            'header'    => $helper->__('Comment'),
            'index'     => 'comment',
            'type'      => 'text',
            'nl2br'     => true,
            'sortable'  => false,
            'filter'   => false,
        ));
        
        return parent::_prepareColumns();
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('customercredit/credit_log')
            ->getCollection()
            ->addCustomerFilter(Mage::registry('current_customer')->getId());
      
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    public function getGridUrl()
    {
        return $this->getUrl('*/*/logGrid', array('_current'=> true));
    }
}