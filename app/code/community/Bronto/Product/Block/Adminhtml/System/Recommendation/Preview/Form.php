<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Preview_Form extends Mage_Adminhtml_Block_Widget_Form
{

    /**
     * Creates the widget containing the dialog popup
     *
     * @param string $field
     * @param string $label
     * @return Mage_Adminhtml_Block_Widget_Button
     */
    protected function _selectButton($field, $label)
    {
        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label' => $this->__($label),
                'onclick' => "selectProducts('{$field}', false);",
                'class' => 'go'
            ));
    }

    /**
     * Prepares the top level preview form
     *
     * @see parent
     */
    protected function _prepareForm()
    {
        $model = Mage::registry('product_recommendation');
        $buttonRenderer = $this->getLayout()->createBlock('bronto_product/adminhtml_system_recommendation_edit_button');

        $form = new Varien_Data_Form(array(
            'id' => 'preview_form',
            'method' => 'post'
        ));

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => $this->__('Preview Information')
        ));

        $fieldset->addField('entity_id', 'select', array(
            'name' => 'entity_id',
            'required' => true,
            'onchange' => "updateRecommendations()",
            'label' => $this->__('Recommendations'),
            'values' => Mage::getModel('bronto_product/recommendation')->toOptionArray()
        ));

        $optional = $fieldset->addField('product_ids', 'text', array(
            'name' => 'product_ids',
            'onchange' => "updateRecommendations()",
            'label' => $this->_selectButton('product_ids', 'Optional Products')->toHtml(),
            'title' => $this->__('Optional Products')
        ));
        $optional->setRenderer($buttonRenderer);

        $correctClass = $model->hasEntityId() ? 'go' : 'disabled';
        $sendMessage = $fieldset->addField('send_message_button', 'label', array(
          'label' => $this->getLayout()->createBlock('adminhtml/widget_button')
              ->setData(array(
                  'label' => $this->__('Send Test Email'),
                  'class' => $correctClass,
                  'onclick' => "messagePicker()",
                  'disabled' => $correctClass == 'disabled'
              ))
              ->toHtml()
        ));
        $sendMessage->setRenderer($buttonRenderer);

        $subfieldset = $fieldset->addFieldset('product_preview', array(
            'legend' => $this->__('Recommended Products'),
        ));

        $grid = new Bronto_Product_Block_Adminhtml_System_Recommendation_Preview_GridElement($this->getLayout()->createBlock('bronto_product/adminhtml_system_recommendation_preview_grid'));
        $subfieldset->addElement($grid);

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
