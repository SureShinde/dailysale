<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipPo_Block_Adminhtml_Report_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('reportGrid');
        $this->setDefaultSort('order_created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    public function t($table)
    {
        return Mage::getSingleton('core/resource')->getTableName($table);
    }

    protected $_couponCodeColumn;
    
    protected function _getFlatExpressionColumn($key, $bypass=true)
    {
    	$result = $bypass ? $key : null;
    	switch ($key) {
            case 'tracking_price':
    			$result = new Zend_Db_Expr("(select sum(IFNULL(st.final_price,0)) from {$this->t('sales_flat_shipment_track')} st where parent_id=shipment_table.entity_id)");
    			break;
    		case 'tracking_ids':
    			$result = new Zend_Db_Expr("(select group_concat(concat(st.".Mage::helper('udropship')->trackNumberField().", ' (', IFNULL(round(st.final_price,2),'N/A'), ')') separator '\\n') from {$this->t('sales_flat_shipment_track')} st where parent_id=shipment_table.entity_id)");
    			break;
    		case 'base_tax_amount':
    			$result = new Zend_Db_Expr("(select sum(base_tax_amount) from {$this->t('sales_flat_order_item')} oi inner join {$this->t('udpo/po_item')} pi where pi.order_item_id=oi.item_id and pi.parent_id=main_table.entity_id and oi.order_id=main_table.order_id)");
    			break;
    		case 'coupon_codes':
		    	if (Mage::helper('udropship')->isModuleActive('Unirgy_Giftcert')) {
					$result = new Zend_Db_Expr("concat(
						IF(o.coupon_code is not null and o.coupon_code!='', concat('Coupon: ',o.coupon_code), ''),
						IF(o.giftcert_code is not null and o.giftcert_code!='', 
							CONCAT(
								IF(o.coupon_code is not null and o.coupon_code!='', '\n', ''),
								concat('Giftcert: ',o.giftcert_code)
							),
							'')
					)");
				} else {
					$result = new Zend_Db_Expr("
						IF(o.coupon_code is not null and o.coupon_code!='', concat('Coupon: ',o.coupon_code), '')
					");
				}
				break;
    	}
    	return $result;
    }
    
    protected function _prepareCollection()
    {
        $res = Mage::getSingleton('core/resource');

        $collection = Mage::getResourceModel('udpo/po_grid_collection');
        $collection->getSelect()
            ->join(array('t'=>$res->getTableName('udpo/po')), 't.entity_id=main_table.entity_id', array('udropship_vendor', 'udropship_available_at', 'udropship_method', 'udropship_method_description', 'udropship_status', 'base_shipping_amount', 'base_subtotal'=>'base_total_value', 'total_cost'))
            ->joinLeft(array('shipment_table'=>$res->getTableName('sales/shipment')), 'shipment_table.udpo_id=main_table.entity_id', array())
            ->join(array('o'=>$res->getTableName('sales/order')), 'o.entity_id=main_table.order_id', array('base_grand_total', 'order_status'=>'o.status'))
            ->join(array('a'=>$res->getTableName('sales/order_address')), 'a.parent_id=o.entity_id and a.address_type="shipping"', array('region_id'))
            ->group('main_table.entity_id')
            ->columns(array(
                'tracking_price'=>$this->_getFlatExpressionColumn('tracking_price'),
                'tracking_ids'=>$this->_getFlatExpressionColumn('tracking_ids'),
                //'subtotal'=>$subtotal,
                'base_tax_amount'=>$this->_getFlatExpressionColumn('base_tax_amount'),
                'coupon_codes' => $this->_getFlatExpressionColumn('coupon_codes')
            ));

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $flat = Mage::helper('udropship')->isSalesFlat();
        
        $hlp = Mage::helper('udropship');
        
        $this->addColumn('order_increment_id', array(
            'header'    => $hlp->__('Order #'),
            'index'     => 'order_increment_id',
            'type'      => 'number',
        ));

        $this->addColumn('order_created_at', array(
            'header'    => $hlp->__('Order Date'),
            'index'     => 'order_created_at',
            'type'      => 'datetime',
        ));
        
        $this->addColumn('order_status', array(
            'header'    => $hlp->__('Order Status'),
            'index'     => 'order_status',
            'filter_index' => !$flat ? null : 'o.status',
            'type' => 'options',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));
        
        $this->addColumn('base_grand_total', array(
            'header' => $hlp->__('Order Total'),
            'index' => 'base_grand_total',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => Mage::getStoreConfig('currency/options/base'),
        ));
        
        $this->addColumn('increment_id', array(
            'header'    => $hlp->__('PO #'),
            'index'     => 'increment_id',
            'filter_index' => !$flat ? null : 'main_table.increment_id',
            'type'      => 'text',
        ));

        $this->addColumn('created_at', array(
            'header'    => $hlp->__('PO Date'),
            'index'     => 'created_at',
            'filter_index' => !$flat ? null : 'main_table.created_at',
            'type'      => 'datetime',
        ));
        
        $this->addColumn('udropship_status', array(
            'header' => $hlp->__('PO Status'),
            'index' => 'udropship_status',
            'filter_index' => !$flat ? null : 'main_table.udropship_status',
            'type' => 'options',
            'options' => Mage::getSingleton('udropship/source')->setPath('shipment_statuses')->toOptionHash(),
        ));

        $this->addColumn('base_subtotal', array(
            'header' => $hlp->__('PO Subtotal'),
            'index' => 'base_subtotal',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => Mage::getStoreConfig('currency/options/base'),
        ));
        
        $this->addColumn('total_cost', array(
            'header' => $hlp->__('PO Total Cost'),
            'index' => 'total_cost',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => Mage::getStoreConfig('currency/options/base'),
        ));

        $this->addColumn('base_tax_amount', array(
            'header' => $hlp->__('PO Tax Amount'),
            'index' => 'base_tax_amount',
        	'filter_index' => !$flat ? null : $this->_getFlatExpressionColumn('base_tax_amount'),
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => Mage::getStoreConfig('currency/options/base'),
        ));
        
        $this->addColumn('base_shipping_amount', array(
            'header' => $hlp->__('PO Shipping Price'),
            'index' => 'base_shipping_amount',
            'filter_index' => !$flat ? null : 'main_table.base_shipping_amount',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => Mage::getStoreConfig('currency/options/base'),
        ));
        
        $this->addColumn('total_qty', array(
            'header'    => $hlp->__('PO Total Qty'),
            'index'     => 'total_qty',
        	'filter_index' => !$flat ? null : 'main_table.total_qty',
            'type'      => 'number',
        ));
        
        $this->addColumn('udropship_vendor', array(
            'header' => $hlp->__('Vendor'),
            'index' => 'udropship_vendor',
            'filter_index' => 'main_table.udropship_vendor',
            'type' => 'options',
            'options' => Mage::getSingleton('udropship/source')->setPath('vendors')->toOptionHash(),
            'filter' => 'udropship/vendor_gridColumnFilter'
        ));
        
        $this->addColumn('tracking_ids', array(
            'header' => $hlp->__('Tracking #'),
            'index' => 'tracking_ids',
        	'filter_index' => !$flat ? null : $this->_getFlatExpressionColumn('tracking_ids'),
        ));

        $this->addColumn('tracking_price', array(
            'header' => $hlp->__('Tracking Total'),
            'index' => 'tracking_price',
        	'filter_index' => !$flat ? null : $this->_getFlatExpressionColumn('tracking_price'),
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => Mage::getStoreConfig('currency/options/base'),
        ));

        $this->addColumn('region_id', array(
            'header' => $hlp->__('Tax State'),
            'index' => 'region_id',
            'type' => 'options',
            'options' => Mage::getSingleton('udropship/source')->getTaxRegions(),
            'filter'    => false,
            'sortable'  => false,
        ));
        
        $this->addColumn('coupon_codes', array(
            'header' => $hlp->__('Order coupon codes'),
            'index' => 'coupon_codes',
        	'filter_index' => !$flat ? null : $this->_getFlatExpressionColumn('coupon_codes'),
        	'type' => 'text',
        	'nl2br' => true,
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('adminhtml')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('adminhtml')->__('XML'));
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
