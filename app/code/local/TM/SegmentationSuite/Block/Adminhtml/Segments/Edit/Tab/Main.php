<?php

class TM_SegmentationSuite_Block_Adminhtml_Segments_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('segmentationsuite_segments');

        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('segment_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            array('legend'=>Mage::helper('segmentationsuite')->__('General Information'), 'class' => 'fieldset-wide'));
        $this->_addElementTypes($fieldset); //register own image element

        if ($model->getId()) {
            $fieldset->addField('segment_id', 'hidden', array(
                'name' => 'segment_id',
            ));
        }

        $fieldset->addField('segment_title', 'text', array(
            'name'      => 'segment_title',
            'label'     => Mage::helper('segmentationsuite')->__('Title'),
            'title'     => Mage::helper('segmentationsuite')->__('Title'),
            'required'  => true
        ));

        $fieldset->addField('store_id', 'multiselect', array(
            'name'      => 'stores[]',
            'label'     => Mage::helper('segmentationsuite')->__('Store View'),
            'title'     => Mage::helper('segmentationsuite')->__('Store View'),
            'required'  => true,
            'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true)
        ));


        $fieldset->addField('segment_status', 'select', array(
            'label'     => Mage::helper('segmentationsuite')->__('Status'),
            'title'     => Mage::helper('segmentationsuite')->__('Status'),
            'name'      => 'segment_status',
            'required'  => true,
            'options'   => array(
                '1' => Mage::helper('segmentationsuite')->__('Enabled'),
                '0' => Mage::helper('segmentationsuite')->__('Disabled')
            )
        ));
        //print_r($model->getData());die;
        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
