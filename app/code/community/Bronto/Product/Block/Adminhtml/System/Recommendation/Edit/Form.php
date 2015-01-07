<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    private $_buttonRenderer;

    /**
     * Creates a select button used for choosing products manually
     *
     * @param string $field
     * @param string $label
     * @return Mage_Adminhtml_Block_Widget_Button
     */
    protected function _selectButton($field, $label = 'Select Products')
    {
        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label' => $this->__($label),
                'onclick' => "selectProducts('{$field}', true);",
                'class' => 'go'
              ));
    }

    /**
     * Gets the internal button renderer
     */
    protected function _getButtonRenderer($new = false)
    {
        if ($new) {
            return $this->getLayout()->createBlock('bronto_product/adminhtml_system_recommendation_edit_button');
        }
        if (is_null($this->_buttonRenderer)) {
            $this->_buttonRenderer = $this->getLayout()->createBlock('bronto_product/adminhtml_system_recommendation_edit_button');
        }
        return $this->_buttonRenderer;
    }

    /**
     * Building the form details
     *
     * @see parent
     */
    protected function _prepareForm()
    {
        $model = Mage::registry('product_recommendation');
        $sources = Mage::getModel('bronto_product/system_config_source_recommendation')->toOptionArray();
        $fallbackSources = Mage::getModel('bronto_product/system_config_source_recommendation')->toOptionArray(null, true);
        $dependence = $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence');

        if ($model->isContentTag()) {
            unset($sources[3], $sources[1]);
            $fallbackSources = $sources;
            if (!$model->hasEntityId()) {
                $model->setTagContent($this->getLayout()->createBlock('bronto_product/adminhtml_system_recommendation_default')->toHtml());
            }
        }
        $model->setExclusionSource(Bronto_Product_Model_Recommendation::SOURCE_CUSTOM);

        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method' => 'post'
        ));

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => $this->__('Recommendation Information')
        ));

        if ($model->hasEntityId()) {
            $fieldset->addField('entity_id', 'hidden', array(
                'name' => 'entity_id'
            ));
        }

        $fieldset->addField('content_type', 'hidden', array(
            'name' => 'content_type',
            'value' => $model->getType()
        ));

        $fieldset->addField('name', 'text', array(
            'name' => 'name',
            'required' => true,
            'label' => $this->__('Name'),
            'title' => $this->__('Name'),
        ));

        $fieldset->addField('number_of_items', 'text', array(
          'name' => 'number_of_items',
          'required' => true,
          'value' => '5',
          'class' => 'validate-digits validate-digits-range digits-range-0-10',
          'label' => $this->__('Number of Items'),
          'title' => $this->__('Number of Items'),
        ));

        if (!Mage::app()->isSingleStoreMode() && $model->isContentTag()) {
            $fieldset->addField('store_id', 'select', array(
                'name' => 'store_id',
                'label' => Mage::helper('adminhtml')->__('Store View'),
                'required' => true,
                'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, false)
            ));
        } else if ($model->isContentTag()) {
            $fieldset->addField('store_id', 'hidden', array(
                'name' => 'store_id',
                'value' => Mage::app()->getStore(true)->getId(),
            ));
        }

        $primarySource = $fieldset->addField('primary_source', 'select', array(
          'name' => 'primary_source',
          'required' => true,
          'label' => $this->__('Primary Source'),
          'title' => $this->__('Primary Source'),
          'values' => $sources
        ));

        $manualPri = $fieldset->addField('manual_primary_source', 'text', array(
            'name' => 'manual_primary_source',
            'label' => $this->_selectButton('manual_primary_source')->toHtml(),
            'title' => $this->__('Custom Products'),
        ));
        $manualPri->setRenderer($this->_getButtonRenderer());

        $manualPriHide = $fieldset->addField('primary_source_hidden', 'hidden', array(
            'name' => 'primary_source_hidden'
        ));

        $secondSource = $fieldset->addField('secondary_source', 'select', array(
            'name' => 'secondary_source',
            'label' => $this->__('Secondary Source'),
            'title' => $this->__('Secondary Source'),
            'values' => $sources
        ));

        $manualSec = $fieldset->addField('manual_secondary_source', 'text', array(
            'name' => 'manual_secondary_source',
            'label' => $this->_selectButton('manual_secondary_source')->toHtml(),
            'title' => $this->__('Custom Products'),
        ));
        $manualSec->setRenderer($this->_getButtonRenderer());

        $manualSecHide = $fieldset->addField('secondary_source_hidden', 'hidden', array(
          'name' => 'secondary_source_hidden'
        ));

        $fallbackSource = $fieldset->addField('fallback_source', 'select', array(
            'name' => 'fallback_source',
            'label' => $this->__('Fallback Source'),
            'title' => $this->__('Fallback Source'),
            'values' => $fallbackSources
        ));

        $manualFal = $fieldset->addField('manual_fallback_source', 'text', array(
            'name' => 'manual_fallback_source',
            'label' => $this->_selectButton('manual_fallback_source')->toHtml(),
            'title' => $this->__('Custom Products'),
        ));
        $manualFal->setRenderer($this->_getButtonRenderer());

        $manualFalHide = $fieldset->addField('fallback_source_hidden', 'hidden', array(
            'name' => 'fallback_source_hidden'
        ));

        $exclusionSource = $fieldset->addField('exclusion_source', 'hidden', array(
            'name' => 'exclusion_source',
            'value' => 'custom',
            'title' => $this->__('Excluded Source')
        ));

        $manualExl = $fieldset->addField('manual_exclusion_source', 'text', array(
            'name' => 'manual_exclusion_source',
            'label' => $this->_selectButton('manual_exclusion_source', 'Exclude Products')->toHtml(),
            'title' => $this->__('Custom Products'),
            'note' => $this->__('Selected Products will be excluded.'),
        ));
        $manualExl->setRenderer($this->_getButtonRenderer());

        $manualExlHide = $fieldset->addField('exclusion_source_hidden', 'hidden', array(
            'name' => 'exclusion_source_hidden',
        ));

        if ($model->isContentTag()) {
            $this->_prepareContentTagBasedForm($fieldset);
        }

        $dependence
            ->addFieldMap($primarySource->getHtmlId(), $primarySource->getName())
            ->addFieldMap($manualPri->getHtmlId(), $manualPri->getName())
            ->addFieldMap($secondSource->getHtmlId(), $secondSource->getName())
            ->addFieldMap($manualSec->getHtmlId(), $manualSec->getName())
            ->addFieldMap($fallbackSource->getHtmlId(), $fallbackSource->getName())
            ->addFieldMap($manualFal->getHtmlId(), $manualFal->getName())
            ->addFieldDependence(
                $manualPri->getName(),
                $primarySource->getName(),
                'custom'
            )
            ->addFieldDependence(
                $manualSec->getName(),
                $secondSource->getName(),
                'custom'
            )
            ->addFieldDependence(
                $manualFal->getName(),
                $fallbackSource->getName(),
                'custom'
            );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        $this->setChild('form_after', $dependence);
        return parent::_prepareForm();
    }

    /**
     * Prepares the WYSIWYG editor
     *
     * @param $fieldset
     * @return void
     */
    protected function _prepareContentTagBasedForm($fieldset)
    {
        $variables = $fieldset->addField('variables', 'label', array(
            'label' => Mage::helper('adminhtml')->__('API Reference'),
            'after_element_html' => '
            <p>Loop tags are to be used inside <code>{dynamic_code}{loop}{/loop}{/dynamic_code}</code>.</p>
            <table id="api_info" style="width: 300px;">
              <thead>
                <tr style="border-bottom: 1px solid #d6d6d6;">
                  <th>Loop Tag Description</th>
                  <th>Loop Tag</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Related Product\'s ID</td>
                  <td>relatedId_#</td>
                </tr>
                <tr>
                  <td>Related Product\'s name</td>
                  <td>relatedName_#</td>
                </tr>
                <tr>
                  <td>Related Product\'s description &nbsp;</td>
                  <td>relatedDescription_#</td>
                </tr>
                <tr>
                  <td>Related Product\'s sku</td>
                  <td>relatedSku_#</td>
                </tr>
                <tr>
                  <td>Related Product\'s price</td>
                  <td>relatedPrice_#</td>
                </tr>
                <tr>
                  <td>Related Product\'s URL</td>
                  <td>relatedUrl_#</td>
                </tr>
                <tr>
                  <td>Related Product\'s image</td>
                  <td>relatedImgUrl_#</td>
                </tr>
              </tbody>
            </table>
            '
        ));

        $content = $fieldset->addField('tag_content', 'textarea', array(
            'label' => $this->__('Content Template'),
            'name' => 'tag_content',
            'required' => true,
            'style' => 'height:25em;width:600px',
        ));

        $previewButton = $fieldset->addField('preview_button', 'text', array(
            'label' => '&nbsp;',
            'container_id' => 'preview_button',
            'note' => $this->__('Content Template will be processed, along with the loop tags associated with the related products.'),
            'button' => $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'class' => 'go',
                    'label' => $this->__('Preview Content')
                ))
        ));
        $previewButton->setRenderer($this->_getButtonRenderer(true)->setDisplayInLabel(false));
    }
}
