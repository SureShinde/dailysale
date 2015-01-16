<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Deals_Block_Adminhtml_Deals_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Set some default on the grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('dealsGrid');
        $this->setDefaultSort('identifier');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    private function updateDealsStatuses()
    {
//        $helper = Mage::helper('fiuze_deals');
//        $collection = Mage::getResourceModel('catalog/product_collection')
//            ->addAttributeToSelect('*')
//            ->addAttributeToFilter('active', true);
//        foreach ($collection as $item) {
//            if ($item->getDealStatus()) {
//                $stat = Mage::helper('fiuze_deals')->checkUpdateDealStatus($item);
//                if ($stat != '')
//                    if ($stat != $item->getDealStatuses()) {
//                        $helper->changeSpecialData($item, $stat);
//                        $item->setDealStatuses($stat)->save();
//
//                    }
//            }
//        }

    }

    protected function getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    /**
     * Set the desired collection on our grid
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $currentCategory = Mage::helper('fiuze_deals')->getCategoryCron();
        $currentCategory = $currentCategory->getProductCollection();
        $currentCategory->addFieldToFilter('status',Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addFieldToFilter('status',Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->joinField(
                'qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )
            ->addAttributeToFilter('qty', array("gt" => 0))
            ->addAttributeToSelect('*');

        if(Mage::getResourceModel('fiuze_deals/deals_collection')->count()){
            $tableName = Mage::getSingleton('core/resource')->getTableName('fiuze_deals/deals');

            $currentCategory->getSelect()->join(
                array('bonus' => $tableName), 'e.entity_id = bonus.product_id',
                array('deals_active' => 'deals_active')
            );
            $currentCategory->getSelect()->join(
                array('bonus1' => $tableName), 'e.entity_id = bonus1.product_id',
                array('deals_price' => 'deals_price')
            );
            $currentCategory->getSelect()->join(
                array('bonus2' => $tableName), 'e.entity_id = bonus2.product_id',
                array('deals_qty' => 'deals_qty')
            );
            $currentCategory->getSelect()->join(
                array('bonus3' => $tableName), 'e.entity_id = bonus3.product_id',
                array('origin_special_price' => 'origin_special_price')
            );
            $currentCategory->getSelect()->join(
                array('bonus4' => $tableName), 'e.entity_id = bonus4.product_id',
                array('sort_order' => 'sort_order')
            );
        }else{
            $this->setCollection(Mage::getResourceModel('fiuze_deals/deals_collection'));
            return parent::_prepareCollection();
        }
        $test = $currentCategory->load()->getItems();
        $this->setCollection($currentCategory);
        return parent::_prepareCollection();
    }

    /**
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $store = $this->getStore();
        $helper = Mage::helper('fiuze_deals');
        $this->addColumn('product_name', array(
            'header' => $helper->__('Product Name'),
            'index' => 'name',
            'width' => '30%',
        ));

        $this->addColumn('product_sku', array(
            'header' => $helper->__('Product Sku'),
            'index' => 'sku',
            'width' => '10%',
        ));

        $this->addColumn('price', array(
            'header' => $helper->__('Price'),
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'type' => 'price',
            'index' => 'price',
            'width' => '5%',
        ));

        $this->addColumn('origin_special_price', array(
            'header' => $helper->__('Origin special price'),
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'type' => 'price',
            'index' => 'origin_special_price',
            'width' => '5%',
        ));

        $this->addColumn('deals_price', array(
            'header' => $helper->__('Deal price'),
            'type' => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'index' => 'deals_price',
            'width' => '5%',
        ));

        $this->addColumn('qty', array(
            'header' => $helper->__('Quantity'),
            'type' => 'number',
            'index' => 'qty',
        ));

        $this->addColumn('deals_qty', array(
            'header' => $helper->__('Deal Quantity'),
            'type' => 'number',
            'index' => 'deals_qty',
        ));

        $this->addColumn('sort_order', array(
            'header' => $helper->__('Sort order'),
            'type' => 'number',
            'index' => 'sort_order',
        ));

        $this->addColumn('deals_active',
            array(
                'header'=> Mage::helper('catalog')->__('Deal status'),
                'width' => '70px',
                'index' => 'deals_active',
                'type'  => 'options',
                'options' => array('0' => 'Disabled','1' =>'Enabled'),
            )
        );

        $this->addColumn('view_deal_product', array(
            'header' => $helper->__('Edit'),
            'type' => 'action',
            'getter' => 'getId',
            'filter' => false,
            'is_system' => true,
            'width' => '5%',
            'actions' => array(
                array(
                    'caption' => $helper->__('Edit'),
                    'url' => array('base' => '*/*/edit/'),
                    'field' => 'id')),
        ));

        $this->addColumn('view_product', array(
            'header' => $helper->__('View Product'),
            'width' => '5%',
            'type' => 'action',
            'getter' => 'getId',
            'filter' => false,
            'is_system'=>true,
            'actions' => array(
                array(
                    'caption' => $helper->__('View Product'),
                    'url' => array('base' => '*/catalog_product/edit/'),
                    'field' => 'id')),
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('banners');

        $this->getMassactionBlock()->addItem('status', array(
            'label' => Mage::helper('adminhtml')->__('Change status'),
            'url' => $this->getUrl('*/*/massStatus', array('_current' => true)),
            'additional' => array(
                'visibility' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('adminhtml')->__('Status'),
                    'values' => Mage::getModel('fiuze_deals/System_Config_Source_Enabling')->toArray()
                )
            )
        ));
        $this->getMassactionBlock()->addItem('remove', array(
            'label' => Mage::helper('adminhtml')->__('Remove deal status'),
            'url' => $this->getUrl('*/*/massRemove'),
        ));
        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}