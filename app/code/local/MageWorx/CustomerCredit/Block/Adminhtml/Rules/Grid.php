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
 
class MageWorx_CustomerCredit_Block_Adminhtml_Rules_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('codeGrid');
        $this->setSaveParametersInSession(true);
        $this->setDefaultSort('rule_id');
        $this->setDefaultDir('desc');
        $this->setUseAjax(true);
    }
	
    protected function _prepareColumns()
    {
	$this->addColumn('rule_id',
            array(
                'header'=> $this->_helper()->__('ID'),
                'width' => '50px',
                'index' => 'rule_id',
        ));
        $this->addColumn('name',
            array(
                'header'=> $this->_helper()->__('Rule Name'),
                'width' => '250px',
                'index' => 'name',
        ));
        $this->addColumn('credit',
            array(
                'header'=> $this->_helper()->__('Credit Amount'),
                'width' => '80px',
       //         'type'  => 'number',
                'index' => 'credit',
        ));
        $options = Mage::getModel('customercredit/rules')->getRuleTypeArray();
        $this->addColumn('rule_type', array(
            'header'    => Mage::helper('salesrule')->__('Rule Type'),
            'index'     => 'rule_type',
            'options'   => $options,
            'type'      => 'options',
            'width'     => '100px'
        ));

        $this->addColumn('website_ids',
            array(
                'header'=> $this->_helper()->__('Website(s)'),
                'type'  => 'options',
                 'width' => '200px',
                'index' => 'website_ids',
            	'options' => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash(),
                'renderer'=> 'customercredit/adminhtml_rules_grid_website'
        ));
        
        $this->addColumn('is_active',
            array(
                'header'=> $this->_helper()->__('Is Active'),
                'width' => 30,
                'type'  => 'options',
                'index' => 'is_active',
            	'options' => array(
            		MageWorx_CustomerCredit_Model_Code::STATUS_ACTIVE => $this->_helper()->__('Yes'),
            		MageWorx_CustomerCredit_Model_Code::STATUS_INACTIVE => $this->_helper()->__('No'),
            	),
        ));
        $this->addColumn('count_rule',
            array(
                'header'=> $this->_helper()->__('Used'),
                'type'  => 'int',
                'width' => '100px',
                'index' => 'count_rule',
                'renderer'=> 'customercredit/adminhtml_rules_grid_count'
        ));
        $this->addColumn('created_at',
            array(
                'header'=> $this->_helper()->__('Created At'),
                'type'  => 'date',
                'width' => '100px',
                'index' => 'created_at',
                'renderer'=> 'customercredit/adminhtml_rules_grid_date'
        ));
    return parent::_prepareColumns();
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('customercredit/rules_collection')
                ->addCounts();
    //               echo $collection->getSelect()->__toString();
        $this->setCollection($collection);
        
    return parent::_prepareCollection();
    }
	
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array(
            'id'    => $row->getId()
        ));
    }
    
    protected function _helper()
    {
    	return Mage::helper('customercredit');
    }
}