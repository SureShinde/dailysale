<?php

/**
 * @package     Bronto\Reminder
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Config_Templates
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    const XML_PATH_TEMPLATE_EMAIL = '//sections/*/groups/*/fields/*[source_model="adminhtml/system_config_source_email_template"]';

    protected $_dummySubset;
    protected $_fieldRenderer;

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setComment("<strong>This form is provided as a centralized location for assigning all Magento email templates.<br />Each section contains a link to where this action would normally be performed.</strong><br /><br />");

        // Only show template mapping if module is enabled
        if (!$this->helper('bronto_email')->isEnabled()) {
            return '';
        }

        $html = $this->_getHeaderHtml($element);
        $html .= $this->_getScopeToggleHtml($element);

        $fields = $this->_getSystemConfigPathsParts();

        $order = 0;
        foreach ($fields as $section => $groups) {
            $html .= $this->_getSubsetHtml($element, $section, $groups, $order);
            $order = $order + 5;
        }

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
     * Adds fields to the child fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $fieldset
     * @param Varien_Object                     $element
     *
     * @return Varien_Data_Form_Element_Abstract
     */
    protected function _addSubFields($fieldset, $element)
    {
        $configCode = 'bronto_email_templates_label_' . $element->getSection();

        if ($element->frontend_model) {
            $fieldRenderer = Mage::getBlockSingleton((string)$element->frontend_model);
        } else {
            $fieldRenderer = $this->_getFieldRenderer();
        }

        $labelLink = $element->getLabel();
        $label     = sprintf('<a href="%s" title="%s">%s</a>',
            $labelLink['url'],
            $labelLink['title'],
            $labelLink['title']
        );

        try {
            $field = $fieldset->addFieldSet($configCode,
                array(
                    'label'                 => $label,
                    'inherit'               => false,
                    'field_config'          => $element,
                    'scope'                 => $this->getForm()->getScope(),
                    'scopeId'               => $this->getForm()->getScopeId(),
                    'can_use_default_value' => $this->getForm()->canUseDefaultValue((int)$element->show_in_default),
                    'can_use_website_value' => $this->getForm()->canUseWebsiteValue((int)$element->show_in_website),
                )
            );

            $fieldRenderer->setForm($this->getForm());
            $field->setRenderer($fieldRenderer);
        } catch (Exception $e) {
            Mage::helper('bronto_customer')->writeDebug('Creating field failed: ' . $e->getMessage());

            return '';
        }

        return $field;
    }

    /**
     * Gets the subfieldset HTML
     *
     * @param Varien_Data_Form_Element_Abstract $fieldset
     * @param string                            $section
     * @param array                             $groups
     * @param int                               $order
     *
     * @return string
     */
    protected function _getSubsetHtml($fieldset, $section, $groups, $order)
    {
        $data    = current($groups);
        $element = $this->_getDummySubset($order);
        $element
            ->setLabel($data['parts'][1])
            ->setSection($section)
            ->setGroups($groups);

        $tempFieldset = $this->_addSubFields($fieldset, $element);
        if (!$tempFieldset) {
            return '';
        }

        return $tempFieldset->toHtml();
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
     * Gets the dummy fieldset config
     *
     * @param int $order
     *
     * @return Varien_Object
     */
    protected function _getDummySubset($order)
    {
        if (empty($this->_dummySubset)) {
            $this->_dummySubset = new Varien_Object(array(
                'sort_order'      => $order,
                'frontend_type'   => 'text',
                'frontend_model'  => 'bronto_email/adminhtml_system_config_templates_fieldset',
                'show_in_store'   => 1,
                'show_in_default' => 1,
                'show_in_website' => 1,
            ));
        }

        return $this->_dummySubset;
    }

    /**
     * Get Array of all config path details
     *
     * @return array
     */
    protected function _getSystemConfigPathsParts()
    {
        $result = $urlParams = $prefixParts = array();
        $paths  = Mage::helper('bronto_email')->getTemplatePaths();

        if ($paths) {
            $prefixParts[] = array(
                'title' => Mage::getSingleton('admin/config')->getMenuItemLabel('system/config'),
            );

            $pathParts = $prefixParts;
            foreach ($paths as $pathData) {
                list($sectionName, $groupName, $fieldName) = explode('/', $pathData);
                $urlParams   = array('section' => $sectionName);
                $scopeParams = Mage::helper('bronto_email')->getScopeParams();

                if (isset($scopeParams['store'])) {
                    $store = Mage::app()->getStore($scopeParams['store']);
                    if ($store) {
                        $urlParams['website'] = $store->getWebsite()->getCode();
                        $urlParams['store']   = $store->getCode();
                    }
                } else if (isset($scopeParams['website'])) {
                    $website = Mage::app()->getWebsite($scopeParams['website']);
                    if ($website) {
                        $urlParams['website'] = $website->getCode();
                    }
                }

                $adminhtmlConfig = Mage::getSingleton('adminhtml/config');
                $adminhtmlConfig->getSections();

                $pathParts[] = array(
                    'title' => $adminhtmlConfig->getSystemConfigNodeLabel($sectionName),
                    'url'   => $this->getUrl('adminhtml/system_config/edit', $urlParams),
                );
                $pathParts[] = array(
                    'title' => $adminhtmlConfig->getSystemConfigNodeLabel($sectionName, $groupName),
                );

                $result[$sectionName][$groupName]['parts']                      = $pathParts;
                $result[$sectionName][$groupName]['fields'][$fieldName]['path'] = $pathData;
                $pathParts                                                      = $prefixParts;
            }
        }

        return $result;
    }
}
