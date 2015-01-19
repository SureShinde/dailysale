<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Deals_Block_Adminhtml_Deals_Products extends Mage_Adminhtml_Block_Widget_Grid
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
    }

    /**
     * Set the desired collection on our grid
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToFilter('visibility', array('neq' => 1))
            ->addAttributeToSelect('*');

        //exclude products available in this category
        if (Mage::getResourceModel('fiuze_deals/deals_collection')->count()) {
            $current = Mage::getResourceModel('fiuze_deals/deals_collection')
                ->addFieldToSelect('product_id')->getData();
            $collection->AddAttributeToFilter ('entity_id', array ('nin' => $current));
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    /**
     *  Set columns on our grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $store = $this->getStore();
        $helper = Mage::helper('fiuze_deals');

        $this->addColumn('title', array(
            'header' => $helper->__('Product Name'),
            'index' => 'name',
            'width' => '30%',
        ));
        $this->addColumn('sku', array(
            'header' => $helper->__('Sku'),
            'index' => 'sku',
        ));

        $this->addColumn('price', array(
            'header' => $helper->__('Price'),
            'type' => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'index' => 'price',
        ));

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn('action', array(
                'header' => $helper->__('Action'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => $helper->__('Add Deal'),
                        'url' => array('base' => '*/*/edit'),
                        'field' => 'id'
                    )
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true,
            ));
        }

        return parent::_prepareColumns();
    }

    /**
     * Get url for row
     *
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/products', array('_current' => true));
    }
}