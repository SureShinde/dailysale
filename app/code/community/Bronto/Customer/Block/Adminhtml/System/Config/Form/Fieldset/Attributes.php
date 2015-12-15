<?php

/**
 * @package   Bronto\Customer
 * @copyright 2011-2012 Bronto Software, Inc.
 */
abstract class Bronto_Customer_Block_Adminhtml_System_Config_Form_Fieldset_Attributes extends Bronto_Common_Block_Adminhtml_System_Config_Form_Fieldset
{
    protected $_ignoreAttributes = array();
    protected $_configPath = '';
    protected $_dummyElement;
    protected $_dummyNewElement;
    protected $_fieldRenderer;
    protected $_values;

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        if (!$this->helper('bronto_customer')->isEnabled()) {
            return '';
        }

        $html = $this->_getHeaderHtml($element);
        $html .= $this->_getScopeToggleHtml($element);

        // Store Labels as Values to set on Create new fields
        $values = array();
        // Render Existing elements
        foreach ($element->getSortedElements() as $field) {
            if ('select' == $field->getType()) {
                $values[$field->getHtmlId() . '_new'] = trim($field->getLabel());
            } else {
                if (array_key_exists($field->getHtmlId(), $values)) {
                    $field->setValue($values[$field->getHtmlId()]);
                }
            }

            $html .= $field->toHtml();
        }

        // Get Array of existing fields
        $skips = $this->_getUsedAttributeCodes($element);
        $order = 100;
        // Cycle through Attributes and skip ignored attributes
        foreach ($this->_getAttributes() as $_attribute) {
            $_attributeCode = $_attribute->getAttributeCode();
            if (in_array($_attributeCode, $skips)) {
                continue;
            } else {
                try {
                    $order = $order + 5;
                    $html .= $this->_getFieldHtml($element, $_attribute, $order);
                } catch (Exception $e) {
                    Mage::helper('bronto_customer')->writeDebug('Creating field failed: ' . $e->getMessage());

                    continue;
                }
            }
        }

        $html .= '
        <script type="text/javascript">
            Event.observe(window, "load", function() {
                $$("fieldset.config select").each(function(item) {
                    item.observe("change", function() {
                        if (item.value == "_new_") {
                            if ($(item.id + "_new") != undefined) {
                                $(item.id + "_new").focus();
                            }
                            if ($(item.id + "_dynamic_new") != undefined) {
                                $(item.id + "_dynamic_new").focus();
                            }
                        }
                    });
                });
            });
        </script>';

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    protected function _getScopeToggleHtml($element)
    {
        $scopeParams = $this->helper('bronto_common')->getScopeParams();
        switch ($scopeParams['scope']) {
            case 'store':
                $inheritLabel = 'Use Website for All';
                break;
            case 'website':
                $inheritLabel = 'Use Default for All';
                break;
            default:
            case 'default':
                return '';
                break;
        }

        $html = '
        <tr id="' . $element->getHtmlId() . '_unselect_all">
            <td class="label"></td>
            <td class="value"></td>
            <td class="use-default" colspan="3">
                <input id="' . $element->getHtmlId() . '_scope_toggle_checkbox" value="0" name="scope_toggle" type="checkbox" class="checkbox config-inherit-toggle" onclick="brontoToggleScope(this, \'' . $element->getHtmlId() . '\');" />
                <label id="' . $element->getHtmlId() . '_scope_toggle_checkbox_label" for="' . $element->getHtmlId() . '_scope_toggle_checkbox" class="inherit" title="Toggle Scope Inheritance">' . $inheritLabel . '</label>
            </td>
        </tr>
        <script type="text/javascript">
            function brontoToggleScope(toggle, parentId)
            {
                var toggleValue = toggle.checked;

                $$("#" + parentId + " input.checkbox.config-inherit").each(function(item){
                    item.checked = toggleValue;
                    item.value = toggleValue ? "1" : "0";
                    toggleValueElements(item, Element.previous(item.parentNode));
                });
            }
        </script>';

        return $html;
    }

    /**
     * this creates a dummy element so you can say if your config fields are available on default and website level -
     * you can skip this and add the scope for each element in _getFieldHtml method
     *
     * @param $order
     *
     * @return Varien_Object
     */
    protected function _getDummyElement($order)
    {
        if (empty($this->_dummyElement)) {
            $this->_dummyElement = new Varien_Object(array(
                'sort_order'      => $order,
                'frontend_type'   => 'select',
                'frontend_model'  => 'bronto_common/adminhtml_system_config_form_field',
                'backend_model'   => 'bronto_customer/system_config_backend_brontofield',
                'source_model'    => 'bronto_common/system_config_source_field',
                'show_in_default' => 1,
                'show_in_website' => 1,
                'show_in_store'   => 1,
            ));
        }

        return $this->_dummyElement;
    }

    /**
     * Get Dummy Element for 'Create New...' form
     *
     * @param $order
     *
     * @return Varien_Object
     */
    protected function _getDummyNewElement($order)
    {
        if (empty($this->_dummyNewElement)) {
            $this->_dummyNewElement = new Varien_Object(array(
                'sort_order'      => $order,
                'frontend_type'   => 'text',
                'backend_model'   => 'bronto_customer/system_config_backend_newfield',
                'show_in_default' => 1,
                'show_in_website' => 1,
                'show_in_store'   => 1,
            ));
        }

        return $this->_dummyNewElement;
    }

    /**
     * this sets the fields renderer. If you have a custom renderer you can change this.
     *
     * @return object
     */
    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $this->_fieldRenderer = Mage::getBlockSingleton('adminhtml/system_config_form_field');
        }

        return $this->_fieldRenderer;
    }

    /**
     * this actually gets the html for a field
     *
     * @param Varien_Data_Form_Element_Abstract $fieldset
     * @param Mage_Eav_Model_Entity_Attribute   $attribute
     * @param int                               $order
     *
     * @return string
     */
    protected function _getFieldHtml(Varien_Data_Form_Element_Abstract $fieldset, Mage_Eav_Model_Entity_Attribute $attribute, $order)
    {
        // Create Select Field
        $e     = $this->_getDummyElement($order);
        $field = $this->_createField($fieldset, $e, $attribute);
        if (!$field) {
            return '';
        }

        // Create New Field
        $en       = $this->_getDummyNewElement($order + 1);
        $newField = $this->_createField($fieldset, $en, $attribute, 'newfield');
        if (!$newField) {
            return '';
        }

        // Define Field Dependencies
        $this->getForm()->getChild('element_dependense')
            ->addFieldMap($field->getHtmlId(), $field->getName())
            ->addFieldMap($newField->getHtmlId(), $newField->getName())
            ->addFieldDependence($newField->getName(), $field->getName(), '_new_');

        return $field->toHtml() . $newField->toHtml();
    }

    /**
     * Create Field and Return it
     *
     * @param Varien_Data_Form_Element_Abstract $fieldset
     * @param Varien_Object                     $e
     * @param Mage_Eav_Model_Entity_Attribute   $attribute
     * @param string                            $fieldStep
     *
     * @return Varien_Data_Form_Element_Abstract
     */
    protected function _createField(
        Varien_Data_Form_Element_Abstract $fieldset,
        Varien_Object $e,
        Mage_Eav_Model_Entity_Attribute $attribute,
        $fieldStep = 'standard'
    )
    {
        // Get Config Data
        $configData = $this->getConfigData();
        if ('' == $attribute->getFrontendLabel()) {
            return false;
        }
        // Define Attribute Code
        $attributeCode = $attribute->getAttributeCode();
        $attributeCode = ($fieldStep == 'newfield') ? "{$attributeCode}_dynamic_new" : $attributeCode;

        // Get Attribute Data and Inheritance
        $path = $this->_configPath . $attributeCode;
        if (isset($configData[$path])) {
            $data    = $configData[$path];
            $inherit = false;
        } else {
            $data    = (string)Mage::getConfig()->getNode(null, $this->getForm()->getScope(), $this->getForm()->getScopeCode())->descend($path);
            $inherit = true;
        }

        // Get field Renderer
        if ($e->frontend_model) {
            $fieldRenderer = Mage::getBlockSingleton((string)$e->frontend_model);
        } else {
            $fieldRenderer = $this->_getFieldRenderer();
        }

        // Define Type, Name, and Label
        $fieldType = (string)$e->frontend_type ? (string)$e->frontend_type : 'text';
        $name      = str_replace('_attrCode_', $attributeCode, $this->_fieldNameTemplate);
        $label     = ($fieldStep == 'newfield') ? "" : $attribute->getFrontendLabel();

        // Pass through backend model in case it needs to modify value
        if ($e->backend_model) {
            $model = Mage::getModel((string)$e->backend_model);
            if (!$model instanceof Mage_Core_Model_Config_Data) {
                Mage::throwException('Invalid config field backend model: ' . (string)$e->backend_model);
            }
            $model->setPath($path)->setValue($data)->afterLoad();
            $data = $model->getValue();
        }

        // Pre-populate New field with label text
        if ('newfield' == $fieldStep && '' == $data) {
            $data = $attribute->getFrontendLabel();
        }

        // Select Field for Existing attributes.
        $field = $fieldset->addField($attributeCode, $fieldType,
            array(
                'name'                  => $name,
                'label'                 => $label,
                'value'                 => ($data === 0) ? '' : $data,
                'inherit'               => ($fieldStep == 'newfield') ? false : $inherit,
                'field_config'          => $e,
                'scope'                 => $this->getForm()->getScope(),
                'scopeId'               => $this->getForm()->getScopeId(),
                'scope_label'           => '[STORE VIEW]',
                'can_use_default_value' => $this->getForm()->canUseDefaultValue((int)$e->show_in_default),
                'can_use_website_value' => $this->getForm()->canUseWebsiteValue((int)$e->show_in_website),
                'can_use_store_value'   => $this->getForm()->canUseWebsiteValue((int)$e->show_in_store),
            ));

        // Add Validation
        if ($e->validate) {
            $field->addClass($e->validate);
        }

        // Determine if value can be empty
        if (isset($e->frontend_type) && 'multiselect' === (string)$e->frontend_type && isset($e->can_be_empty)) {
            $field->setCanBeEmpty(true);
        }

        // Set Field Renderer
        $field->setRenderer($fieldRenderer);

        // Use Source Model to define available options
        if ($e->source_model) {
            $sourceModel = Mage::getSingleton((string)$e->source_model);
            if ($sourceModel instanceof Varien_Object) {
                $sourceModel->setPath($path);
            }
            $field->setValues($sourceModel->toOptionArray());
        }

        return $field;
    }

    abstract protected function _getAttributes();

    /**
     * @param Varien_Data_Form_Element_Abstract $fieldset
     *
     * @return array
     */
    protected function _getUsedAttributeCodes(Varien_Data_Form_Element_Abstract $fieldset)
    {
        $usedAttributeCodes = array();
        foreach ($fieldset->getSortedElements() as $_element) {
            // Determine the *actual* name for this select box
            preg_match('/\[(\w+)\]\[value\]/', $_element->getName(), $matches);
            if (isset($matches[1])) {
                // Add to list
                $usedAttributeCodes[] = $matches[1];
            }
        }

        // Merge in ignored attribute codes
        $usedAttributeCodes = array_merge($usedAttributeCodes, $this->_ignoreAttributes);

        return $usedAttributeCodes;
    }
}
