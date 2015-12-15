<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Override parent constructor for some grid specific things
     *
     * @param array $attributes (Optional)
     * @see parent
     */
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);
        $this->setId('recommendationGrid');
        $this->setIdFieldName('entity_id');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepares the data collection
     *
     * @return Bronto_Product_Block_Adminhtml_System_Recommendation_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('bronto_product/recommendation')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepares the mass action block
     *
     * @return Bronto_Product_Block_Adminhtml_System_Recommendation_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('id');

        $this->getMassactionBlock()->addItem('copy', array(
            'label' => $this->__('Copy'),
            'confirm' => $this->__('Are you sure?'),
            'url' => $this->getUrl('*/*/copy')
        ));

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => $this->__('Delete'),
            'confirm' => $this->__('Are you sure?'),
            'url' => $this->getUrl('*/*/delete')
        ));
        return $this;
    }

    /**
     * Gathers the sources from the dropdown sources
     *
     * @return array
     */
    protected function _getSources($fallback = false)
    {
        $sources = array();
        $model = Mage::getModel('bronto_product/system_config_source_recommendation');
        foreach ($model->toOptionArray(true) as $index => $optgroup) {
            if ($index === 0 && $fallback) {
                continue;
            }
            foreach ($optgroup['value'] as $option) {
                $sources[$option['value']] = $option['label'];
            }
        }
        return $sources;
    }

    /**
     * Prepares the columns of the grid
     *
     * @see parent
     */
    protected function _prepareColumns()
    {
        $helper = Mage::helper('bronto_product');
        $sources = $this->_getSources();

        $this->addColumn('entity_id', array(
            'header' => $helper->__('ID'),
            'align' => 'left',
            'index' => 'entity_id',
            'type' => 'number',
            'filter' => false
          ));

        $this->addColumn('name', array(
            'header' => $helper->__('Name'),
            'index' => 'name',
        ));

        $this->addColumn('number_of_items', array(
            'header' => $helper->__('# of Items'),
            'index' => 'number_of_items',
            'type' => 'number',
            'filter' => false,
            'sortable' => false
        ));

        $this->addColumn('content_type', array(
            'header' => $helper->__('Content Type'),
            'index' => 'content_type',
            'type' => 'options',
            'options' => Mage::getModel('bronto_product/content')->toOptionArray(),
        ));

        $this->addColumn('primary_source', array(
            'header' => $helper->__('Primary Source'),
            'index' => 'primary_source',
            'type' => 'options',
            'options' => $sources,
         ));

        $this->addColumn('secondary_source', array(
            'header' => $helper->__('Secondary Source'),
            'index' => 'secondary_source',
            'type' => 'options',
            'options' => $sources,
        ));

        $this->addColumn('fallback_source', array(
            'header' => $helper->__('Fallback Source'),
            'index' => 'fallback_source',
            'type' => 'options',
            'options' => $this->_getSources(true),
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => $helper->__('Store View'),
                'type' => 'store',
                'skipAllStoresLabel' => true,
                'index' => 'store_id',
                'sortable' => false,
                'store_view' => true,
            ));
        }

        $this->addColumn('action', array(
            'header' => $helper->__('Action'),
            'index' => 'entity_id',
            'sortable' => false,
            'filter' => false,
            'width' => '130px',
            'renderer' => 'bronto_product/adminhtml_system_recommendation_grid_renderer_action'
        ));

        return $this;
    }

    /**
     * Gets the edit url for a given recommendation
     *
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}
