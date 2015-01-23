<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Email_Import_Grid extends Mage_Adminhtml_Block_System_Email_Template_Grid
{

    /**
     * Turn off AJAX for this grid, as it kicks back to the Dashboard
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setUseAjax(false);
    }

    protected function _prepareCollection()
    {
        /* @var $collection Bronto_Email_Model_Mysql4_Template_Collection */
        $collection = Mage::getModel('bronto_email/template')->getCollection();

        $templateTable = Mage::getSingleton('core/resource')->getTableName('bronto_email/template');
        $brontoTable   = Mage::getSingleton('core/resource')->getTableName('bronto_email/message');

        // Apply conditional logic to handle 1.9 overriding collection _construct
        if (Mage::helper('bronto_common')->isVersionMatch(Mage::getVersionInfo(), 1, array(4, 5, array('edition' => 'Professional', 'major' => 9), 10))) {
            $collection->getSelect()->joinLeft(
                $brontoTable,
                "`{$templateTable}`.`template_id` = `{$brontoTable}`.`core_template_id`"
            );
        }

        // Limit grid to show only those templates without message id assigned
        $collection->addFieldToFilter("`{$brontoTable}`.`bronto_message_id`", array('null' => true));

        $this->setCollection($collection);

        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

    /**
     * Add columns to the grid
     *
     * @return Bronto_Email_Block_Adminhtml_System_Email_Template_Grid
     */
    protected function _prepareColumns()
    {

        parent::_prepareColumns();

        if (Mage::helper('bronto_email')->isEnabledForAny()) {
            $this->addColumn('action', array(
                'header'   => Mage::helper('adminhtml')->__('Action'),
                'index'    => 'template_id',
                'sortable' => false,
                'filter'   => false,
                'width'    => '100px',
                'renderer' => 'bronto_email/adminhtml_system_email_import_grid_renderer_action'
            ));
        }

        return $this;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('template_id');
        $this->getMassactionBlock()->setFormFieldName('template_id');

        $stores = Mage::app()->getStores(true);
        if (is_array($stores) && count($stores) >= 1) {
            foreach ($stores as $store) {
                if (Mage::helper('bronto_email')->isEnabled('store', $store->getId())) {
                    $this->getMassactionBlock()->addItem('import|' . $store->getCode(), array(
                            'url'     => $this->getUrl('*/*/massImport', array('template_id' => '', 'store_id' => $store->getId())),
                            'label'   => Mage::helper('bronto_email')->__('Import For Store: ' . (!$store->getId() ? 'Default' : $store->getName())),
                            'confirm' => Mage::helper('bronto_email')->__('Are you sure?  This will import the selected template(s) to Bronto for the specified store.'),
                        ));
                }
            }
        }

        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => Mage::helper('bronto_email')->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete', array('delete_level' => 'full')),
            'confirm' => Mage::helper('bronto_email')->__('Are you sure?  This will permanently delete the selected template(s). Please note: default templates can be re-loaded, but custom templates will be lost.')
        ));

        return $this;
    }

    /**
     * get Row Url for editing template on row click
     *
     * @param $row
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/importEdit', array('id' => $row->getId()));
    }

}
