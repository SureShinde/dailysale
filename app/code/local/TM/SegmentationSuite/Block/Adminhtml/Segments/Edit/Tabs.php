<?php

class TM_SegmentationSuite_Block_Adminhtml_Segments_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('segments_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('segmentationsuite')->__('Segment Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('main_section', array(
            'label'     => Mage::helper('segmentationsuite')->__('General'),
            'title'     => Mage::helper('segmentationsuite')->__('General'),
            'content'   => $this->getLayout()->createBlock('segmentationsuite/adminhtml_segments_edit_tab_main')->toHtml(),
            'active'    => true
        ));

        $this->addTab('conditions_section', array(
            'label'     => Mage::helper('segmentationsuite')->__('Conditions'),
            'title'     => Mage::helper('segmentationsuite')->__('Conditions'),
            'content'   => $this->getLayout()->createBlock('segmentationsuite/adminhtml_segments_edit_tab_conditions')->toHtml()
        ));

        $this->addTab('customers_section', array(
            'label'     => Mage::helper('segmentationsuite')->__('Segment Customers'),
            'title'     => Mage::helper('segmentationsuite')->__('Segment Customers'),
            'content'   => $this->getLayout()->createBlock('segmentationsuite/adminhtml_segments_edit_tab_customers')->toHtml()
        ));

        return parent::_beforeToHtml();
    }
}