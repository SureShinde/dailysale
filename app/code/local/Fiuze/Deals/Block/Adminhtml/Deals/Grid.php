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
        $tableName = Mage::getSingleton('core/resource')->getTableName('fiuze_deals/deals');
        $this->updateDealsStatuses();
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect('*');
            //->addAttributeToFilter('deal_status', array("notnull" => true));

        //$collection->joinAttribute('deal_statuses', 'catalog_product/deal_statuses', 'entity_id', null, 'inner');
//        $collection->getSelect()->joinLeft($tableName, 'at_deal_statuses.value = '.$tableName.'.code', array(
//            'deal_statuses' => 'title',
//        ));
        $this->setCollection($collection);
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
        ));

        $this->addColumn('product_sku', array(
            'header' => $helper->__('Product Sku'),
            'index' => 'sku',
        ));

        $this->addColumn('price', array(
            'header' => $helper->__('Price'),
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'type' => 'price',
            'index' => 'price',
        ));

        $this->addColumn('deal_price', array(
            'header' => $helper->__('Deal price'),
            'type' => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'index' => 'deal_price',
        ));

        $this->addColumn('qty', array(
            'header' => $helper->__('Quantity'),
            'type' => 'number',
            'index' => 'qty',
        ));

        $this->addColumn('deal_qty', array(
            'header' => $helper->__('Deal Quantity'),
            'type' => 'number',
            'index' => 'deal_qty',
        ));


        $this->addColumn('view_deal_product', array(
            'header' => $helper->__('Edit'),
            'type' => 'action',
            'getter' => 'getId',
            'filter' => false,
            'is_system' => true,
            'actions' => array(
                array(
                    'caption' => $helper->__('Edit'),
                    'url' => array('base' => '*/*/edit/'),
                    'field' => 'id')),
        ));

        $this->addColumn('view_product', array(
            'header' => $helper->__('View Product'),
            'width' => '100',
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