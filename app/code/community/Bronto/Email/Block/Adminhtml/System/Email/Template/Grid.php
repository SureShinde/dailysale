<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Email_Template_Grid extends Mage_Adminhtml_Block_System_Email_Template_Grid
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('systemBrontoEmailTemplateGrid');
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

        // Limit grid to show only those templates with message id assigned
        $collection->addFieldToFilter("`{$brontoTable}`.`bronto_message_id`", array('notnull' => true));

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
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            return parent::_prepareColumns();
        }

        $this->addColumn(
            'template_id', array(
                'header' => Mage::helper('adminhtml')->__('ID'),
                'index'  => 'template_id',
                'width'  => '30px',
            )
        );

        $this->addColumn(
            'added_at', array(
                'header'    => Mage::helper('adminhtml')->__('Date Added'),
                'index'     => 'added_at',
                'gmtoffset' => true,
                'type'      => 'datetime'
            )
        );

        $this->addColumn(
            'modified_at', array(
                'header'    => Mage::helper('adminhtml')->__('Date Updated'),
                'index'     => 'modified_at',
                'gmtoffset' => true,
                'type'      => 'datetime'
            )
        );

        $this->addColumn(
            'template_code', array(
                'header' => Mage::helper('adminhtml')->__('Name'),
                'index'  => 'template_code'
            )
        );

        $this->addColumn(
            'message_name', array(
                'header' => Mage::helper('adminhtml')->__('Bronto Message'),
                'index'  => 'bronto_message_name',
            )
        );

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => Mage::helper('adminhtml')->__('Store View'),
                'type' => 'store',
                'index' => 'store_id',
                'sortable' => true,
                'store_view' => true
            ));
        }

        $this->addColumn(
            'template_send_type',
            array(
                'header'  => Mage::helper('adminhtml')->__('Send Type'),
                'index'   => 'template_send_type',
                'type'    => 'options',
                'options' => array(
                    'marketing'     => 'Bronto Marketing',
                    'transactional' => 'Bronto Transactional',
                    'magento'       => 'Magento Email',
                ),
            )
        );

        $this->addColumn('action', array(
            'header'   => Mage::helper('adminhtml')->__('Action'),
            'index'    => 'template_id',
            'sortable' => false,
            'filter'   => false,
            'width'    => '130px',
            'renderer' => 'bronto_email/adminhtml_system_email_template_grid_renderer_action'
        ));

        return $this;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('template_id');
        $this->getMassactionBlock()->setFormFieldName('template_id');
        $this->getMassactionBlock()->addItem('marketing', array(
            'label'   => Mage::helper('bronto_email')->__('Set to send as Bronto Marketing'),
            'url'     => $this->getUrl('*/*/updateSendType', array('send_type' => 'marketing')),
            'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to set the selected template(s) to send through Bronto as a marketing message?')
        ));
        $this->getMassactionBlock()->addItem('transactional', array(
            'label'   => Mage::helper('bronto_email')->__('Set to send as Bronto Transactional'),
            'url'     => $this->getUrl('*/*/updateSendType', array('send_type' => 'transactional')),
            'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to set the selected template(s) to send through Bronto as a transactional message?')
        ));
        $this->getMassactionBlock()->addItem('magento', array(
            'label'   => Mage::helper('bronto_email')->__('Set to send as Magento Email'),
            'url'     => $this->getUrl('*/*/updateSendType', array('send_type' => 'magento')),
            'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to set the selected template(s) to send through Magento?')
        ));
        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => Mage::helper('bronto_email')->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete', array('' => '')),
            'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to delete the selected template(s)?  If any of the selected template(s) are currently assigned to be used, those will automatically be reassigned to the default Magento template(s).')
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
        return $this->getUrl('*/*/brontoEdit', array('id' => $row->getId()));
    }

}
