<?php

class Bronto_Email_Block_Adminhtml_System_Config_Templates_Fieldset extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

    protected $_dummyElement;
    protected $_dummyLabel;
    protected $_fieldRenderer;

    /**
     * @see parent
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getHeaderHtml($element)
    {
        return '<tr id="' . $element->getId() . '"><td colspan="5">
                <fieldset><legend style="font-weight:bold; visibility:inherit; font-size:16px; width:inherit; height:inherit; line-height:inherit;">' . $element->getLabel() . '</legend>
                <table class="form-list" cellspacing="0">';
    }

    /**
     * @see parent
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getFooterHtml($element)
    {
        return '</table></fieldset></td></tr>';
    }

    /**
     * @see parent
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html  = $this->_getHeaderHtml($element);
        $order = 0;
        foreach ($element->getFieldConfig()->getGroups() as $group => $groupData) {
            $order = $order + 5;
            $html .= $this->_getLabelHtml($element, $element->getFieldConfig()->getSection(), $group, $groupData, $order);
            foreach ($groupData['fields'] as $field) {
                try {
                    $order = $order + 5;
                    $html .= $this->_getFieldHtml($element, $field, $order);
                } catch (Exception $e) {
                    Mage::helper('bronto_customer')->writeDebug('Creating field failed: ' . $e->getMessage());
                }
            }
        }

        $html .= $this->_getFooterHtml($element);

        return $html;
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
     * Get HTML for field element
     *
     * @param Varien_Data_Form_Element_Abstract $fieldset
     * @param array                             $field
     * @param int                               $order
     *
     * @return string
     */
    protected function _getFieldHtml(Varien_Data_Form_Element_Abstract $fieldset, array $field, $order)
    {
        // Create Select Field
        $element   = $this->_getDummyElement($order);
        $tempField = $this->_createField($fieldset, $element, $field);
        if (!$tempField) {
            return '';
        }

        return $tempField->toHtml();
    }

    /**
     * Get HTML for label element
     *
     * @param Varien_Data_Form_Element_Abstract $fieldset
     * @param string                            $section
     * @param string                            $group
     * @param array                             $groupData
     * @param int                               $order
     *
     * @return string
     */
    protected function _getLabelHtml(Varien_Data_Form_Element_Abstract $fieldset, $section, $group, array $groupData, $order)
    {
        $element = $this->_getDummyLabel($order);
        $label   = $this->_createLabel($fieldset, $element, $groupData, $section, $group);
        if (!$label) {
            return '';
        }

        return $label->toHtml();
    }

    /**
     * Get dummy field element to set specific configurations
     *
     * @param int $order
     *
     * @return Varien_Object
     */
    protected function _getDummyElement($order)
    {
        if (empty($this->_dummyElement)) {
            $this->_dummyElement = new Varien_Object(array(
                'sort_order'      => $order,
                'frontend_type'   => 'select',
                'frontend_model'  => 'bronto_email/adminhtml_system_config_templates_field',
                'backend_model'   => 'bronto_email/system_config_backend_templates_field',
                'source_model'    => 'bronto_email/system_config_source_email_template',
                'show_in_default' => 1,
                'show_in_website' => 1,
                'show_in_store'   => 1,
            ));
        }

        return $this->_dummyElement;
    }

    /**
     * Get dummy label element to set specific configurations
     *
     * @param int $order
     *
     * @return Varien_Object
     */
    protected function _getDummyLabel($order)
    {
        if (empty($this->_dummyLabel)) {
            $this->_dummyLabel = new Varien_Object(array(
                'sort_order'      => $order,
                'frontend_type'   => 'label',
                'frontend_model'  => 'bronto_email/adminhtml_system_config_templates_label',
                'show_in_default' => 1,
                'show_in_website' => 1,
                'show_in_store'   => 1,
            ));
        }

        return $this->_dummyLabel;
    }

    /**
     * Create Field and Return it
     *
     * @param Varien_Data_Form_Element_Abstract $fieldset
     * @param Varien_Object                     $element
     * @param array                             $field
     *
     * @return Varien_Data_Form_Element_Abstract
     */
    protected function _createField(
        Varien_Data_Form_Element_Abstract $fieldset,
        Varien_Object $element,
        array $field
    )
    {
        // Get Attribute Data and Inheritance
        $path = $field['path'];

        // Get Config Data
        $configData = $this->getConfigData($path);

        // Build Id and Code from Path
        $fieldId   = implode('-', explode('/', $field['path']));
        $fieldCode = 'bronto_email_templates_' . $fieldId;

        $data    = false;
        $inherit = true;
        if (array_key_exists($path, $configData)) {
            $data    = $configData[$path]['data'];
            $inherit = $configData[$path]['inherit'];
        }

        if (!$data) {
            $data    = (string)Mage::getConfig()->getNode(null, $this->getForm()->getScope(), $this->getForm()->getScopeCode())->descend($path);
            $inherit = true;
        }

        // Get field Renderer
        if ($element->frontend_model) {
            $fieldRenderer = Mage::getBlockSingleton((string)$element->frontend_model);
        } else {
            $fieldRenderer = $this->_getFieldRenderer();
        }

        // Define Type, Name, and Label
        $fieldType = (string)$element->frontend_type ? (string)$element->frontend_type : 'text';
        $name      = 'groups[templates][fields][' . $fieldId . '][value]';

        // Build Field Label from path
        $pathParts = explode('/', $path);
        $labelPart = array_pop($pathParts);
        $label     = str_replace('_', ' ', uc_words($labelPart));

        // Pass through backend model in case it needs to modify value
        if ($element->backend_model) {
            $model = Mage::getModel((string)$element->backend_model);
            if (!$model instanceof Mage_Core_Model_Config_Data) {
                Mage::throwException('Invalid config field backend model: ' . (string)$element->backend_model);
            }
            $model->setPath($path)->setValue($data)->afterLoad();
            $data = $model->getValue();
        }

        // Select Field for Existing attributes.
        $field = $fieldset->addField($fieldCode, $fieldType,
            array(
                'name'                  => $name,
                'label'                 => $label,
                'value'                 => $data,
                'inherit'               => $inherit,
                'field_config'          => $element,
                'scope'                 => $this->getForm()->getScope(),
                'scopeId'               => $this->getForm()->getScopeId(),
                'scope_label'           => $this->getForm()->getScopeLabel($element),
                'can_use_default_value' => $this->getForm()->canUseDefaultValue((int)$element->show_in_default),
                'can_use_website_value' => $this->getForm()->canUseWebsiteValue((int)$element->show_in_website),
            )
        );

        // Add Validation
        if ($element->validate) {
            $field->addClass($element->validate);
        }

        // Determine if value can be empty
        if (isset($element->frontend_type) && 'multiselect' === (string)$element->frontend_type && isset($element->can_be_empty)) {
            $field->setCanBeEmpty(true);
        }

        // Set Field Renderer
        $field->setRenderer($fieldRenderer);

        // Use Source Model to define available options
        if ($element->source_model) {
            $sourceModel = Mage::getSingleton((string)$element->source_model);
            if ($sourceModel instanceof Varien_Object) {
                $sourceModel->setPath($path);
            }

            $field->setValues($sourceModel->toOptionArray());
        }

        return $field;
    }

    /**
     * Create Label and Return it
     *
     * @param Varien_Data_Form_Element_Abstract $fieldset
     * @param Varien_Object                     $element
     * @param array                             $groupData
     * @param                                   $section
     * @param                                   $group
     *
     * @return string|Varien_Data_Form_Element_Abstract
     */
    protected function _createLabel(
        Varien_Data_Form_Element_Abstract $fieldset,
        Varien_Object $element,
        array $groupData,
        $section,
        $group
    )
    {
        $configCode = 'bronto_email_templates_label_' . $section . '_' . $group;

        $data    = $groupData['parts'][2]['title'];
        $inherit = false;

        // Get field Renderer
        if ($element->frontend_model) {
            $fieldRenderer = Mage::getBlockSingleton((string)$element->frontend_model);
        } else {
            $fieldRenderer = $this->_getFieldRenderer();
        }

        // Define Type, Name, and Label
        $fieldType = 'label';
        $label     = $data;

        try {
            // Select Field for Existing attributes.
            $field = $fieldset->addField($configCode, $fieldType,
                array(
                    'label'                 => $label,
                    'inherit'               => $inherit,
                    'field_config'          => $element,
                    'scope'                 => $this->getForm()->getScope(),
                    'scopeId'               => $this->getForm()->getScopeId(),
                    'can_use_default_value' => $this->getForm()->canUseDefaultValue((int)$element->show_in_default),
                    'can_use_website_value' => $this->getForm()->canUseWebsiteValue((int)$element->show_in_website),
                )
            );

            // Set Field Renderer
            $field->setRenderer($fieldRenderer);
        } catch (Exception $e) {
            Mage::helper('bronto_customer')->writeDebug('Creating field failed: ' . $e->getMessage());

            return '';
        }

        return $field;
    }

    /**
     * Override getConfigData to handle us altering the path
     *
     * @param $path
     *
     * @return array
     */
    public function getConfigData($path)
    {
        $configData = array();
        /* @var $configDataCollection Mage_Core_Model_Resource_Config_Data_Collection */
        $configDataCollection = $this->_getConfigCollection($path);

        // Get Scope
        $scope   = $this->getForm()->getScope();
        $scopeId = $this->getForm()->getScopeId();

        $inherit = false;
        if ($configDataCollection->count()) {
            while ($scope) {
                foreach ($configDataCollection as $config) {
                    if (
                        ($scope == $config->getScope() || $scope . 's' == $config->getScope()) &&
                        ($scopeId == $config->getScopeId()) &&
                        (!is_null($config->getValue()))
                    ) {
                        $configData[$path]['data']    = $config->getValue();
                        $configData[$path]['inherit'] = $inherit;

                        return $configData;
                    }
                }
                $scopeParent = $this->_getScopeParent($scope, $scopeId);
                $scope       = $scopeParent['scope'];
                $scopeId     = $scopeParent['scope_id'];
                $inherit     = true;
            }
        }

        return $configData;
    }

    /**
     * Get Config Data Collection for Path
     *
     * @param $path
     *
     * @return Mage_Core_Model_Resource_Config_Data_Collection
     */
    protected function _getConfigCollection($path)
    {
        /* @var $configDataCollection Mage_Core_Model_Resource_Config_Data_Collection */
        $configDataCollection = Mage::getModel('core/config_data')->getCollection()
            ->addFieldToFilter('path', $path);

        return $configDataCollection;
    }

    /**
     * Get Parent Scope
     *
     * @param string     $scope
     * @param string|int $scopeId
     *
     * @return array
     */
    protected function _getScopeParent($scope, $scopeId)
    {
        if ('store' == $scope || 'website' == $scope) {
            $scope .= 's';
        }

        switch ($scope) {
            case 'stores':
                $scope   = 'websites';
                $scopeId = Mage::app()->getStore($scopeId)->getWebsiteId();
                break;
            case 'websites':
                $scope   = 'default';
                $scopeId = 0;
                break;
            case 'default':
            default:
                $scope   = false;
                $scopeId = false;
                break;
        }

        return array('scope' => $scope, 'scope_id' => $scopeId);
    }
}
