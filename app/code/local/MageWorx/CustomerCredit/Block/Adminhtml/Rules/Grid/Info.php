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
 
class MageWorx_CustomerCredit_Block_Adminhtml_Rules_Grid_Info extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
       
        parent::__construct();
        $this->setId('entity_id');
        $this->setUseAjax(true);
        $this->setDefaultSort('action_time');
        $this->setSaveParametersInSession(true);
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('customercredit/customer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('email');
        $collection->getSelect()
            ->join(array('credit'=>$collection->getTable('customercredit/credit')),"e.entity_id=credit.customer_id",array())
            ->join(array('credit_log'=>$collection->getTable('customercredit/credit_log')),"credit.credit_id=credit_log.credit_id",array("action_date"))
            ->where('credit_log.rule_id=?',Mage::app()->getRequest()->getParam('rule'))
            ;
    
//echo $collection->getSelect()->__toString();
      $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
//          echo "<pre>"; print_r(Mage::app()->getRequest()->getParams()); echo "</pre>";
//          echo "<pre>"; print_r(Mage::app()->getRequest()->getParams()); echo "</pre>";
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('customercredit')->__('ID'),
            'width'     => '50px',
            'index'     => 'entity_id',
            'type'      => 'number',
            'sortable'  => FALSE,
            'filter'    => FALSE,
            'search'    => FALSE
        ));
//      
        $this->addColumn('name', array(
            'header'    => Mage::helper('customercredit')->__('Name'),
            'index'     => 'name',
//            'filter'    => FALSE,
//            'search'    => FALSE
        ));
        $this->addColumn('name', array(
            'header'    => Mage::helper('customercredit')->__('Name'),
            'index'     => 'name',
//            'filter'    => FALSE,
//            'search'    => FALSE
        ));
        $this->addColumn('email', array(
            'header'    => Mage::helper('customercredit')->__('Email'),
            'width'     => '150',
            'index'     => 'email',
//            'filter'    => FALSE,
//            'search'    => FALSE
        ));
        
        $this->addColumn('action_date', array(
            'header'   => Mage::helper('customercredit')->__('Used On'),
//            'index'    => 'action_date',
            'type'      => 'datetime',
            'width'     => '160px',
            'index'     => 'action_date',
            'filter_index'=> 'credit_log.action_date',
            'filter'    => FALSE,
            'search'    => FALSE,
            'sortable'  => FALSE
        ));
        
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('website_id', array(
                'header'    => Mage::helper('customercredit')->__('Website'),
                'align'     => 'center',
                'width'     => '80px',
                'type'      => 'options',
                'options'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash(true),
                'index'     => 'website_id',
//            'filter'    => FALSE,
//            'search'    => FALSE
            ));
        }

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/info', array('_current'=> true,'rule'=>Mage::app()->getRequest()->getParam('rule')));
    }

}