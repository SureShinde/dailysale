<?php

class TM_SegmentationSuite_Block_Adminhtml_Segments_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('segmentationsuite_segments');

        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('segments_');

        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('segmentationsuite/segments/filters.phtml')
            ->setNewChildUrl($this->getUrl('*/*/newConditionHtml/form/segments_conditions_fieldset'));

        $fieldset = $form->addFieldset('conditions_fieldset', array(
            'legend'=>Mage::helper('segmentationsuite')->__('Conditions (leave blank to show on all pages)'))
        )->setRenderer($renderer);

        $fieldset->addField('conditions', 'text', array(
                'name'      => 'conditions',
                'label'     => Mage::helper('segmentationsuite')->__('Conditions'),
                'title'     => Mage::helper('segmentationsuite')->__('Conditions'),
                'required'  => true,
            ))
            ->setRule($model)
            ->setRenderer(Mage::getBlockSingleton('rule/conditions'));

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
