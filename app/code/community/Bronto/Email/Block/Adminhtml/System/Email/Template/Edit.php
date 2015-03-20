<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Email_Template_Edit extends Mage_Adminhtml_Block_System_Email_Template_Edit
{
    public function __construct()
    {
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            return parent::__construct();
        }

        Mage_Adminhtml_Block_Widget::__construct();
        $this->setTemplate('bronto/email/template/edit.phtml');

        return $this;
    }

    /**
     * Prepare the layout, removing unneeded elements and changing button/form
     *
     * @return null
     */
    protected function _prepareLayout()
    {
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            return parent::_prepareLayout();
        }

        parent::_prepareLayout();

        $this->unsetChild('to_plain_button');
        $this->unsetChild('to_html_button');
        $this->unsetChild('preview_button');
        $this->unsetChild('form');

        $this->setChild('save_button', $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
            'label'   => Mage::helper('adminhtml')->__('Save Message'),
            'onclick' => 'templateControl.save();',
            'class'   => 'save'
        )));

        $this->setChild('form',
            $this->getLayout()->createBlock('bronto_email/adminhtml_system_email_template_edit_form')
        );

        return $this;
    }

    /**
     * Return header text for form
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            return parent::getHeaderText();
        }

        if ($this->getEditMode()) {
            return Mage::helper('adminhtml')->__('Edit Template');
        }

        return Mage::helper('adminhtml')->__('New Template');
    }

    /**
     * Get array or Json of path data
     *
     * @param bool $asJSON
     *
     * @return array|string
     */
    public function getUsedDefaultForPaths($asJSON = true)
    {
        $paths = $this->getEmailTemplate()->getSystemConfigPathsWhereUsedAsDefault();
        if (Mage::helper('bronto_email')->isEnabledForAny()) {
            if ($this->getEmailTemplate()->hasData('store_id')) {
                $paths[0]['scope_id'] = $this->getEmailTemplate()->getData('store_id');
                $paths[0]['scope']    = 'stores';
            }
        }

        $pathsParts = $this->_getSystemConfigPathsParts($paths);

        if ($asJSON) {
            return Mage::helper('core')->jsonEncode($pathsParts);
        }

        return $pathsParts;
    }

    /**
     * Get paths of where current template is currently used
     *
     * @param bool $asJSON
     *
     * @return string
     */
    public function getUsedCurrentlyForPaths($asJSON = true)
    {
        $paths      = $this->getEmailTemplate()->getSystemConfigPathsWhereUsedCurrently();
        $pathsParts = $this->_getSystemConfigPathsParts($paths);
        if ($asJSON) {
            return Mage::helper('core')->jsonEncode($pathsParts);
        }

        return $pathsParts;
    }

    /**
     * Convert xml config paths to decorated names
     *
     * @param array $paths
     *
     * @return array
     */
    protected function _getSystemConfigPathsParts($paths)
    {
        $result     = $urlParams = $prefixParts = array();
        $scopeLabel = Mage::helper('adminhtml')->__('GLOBAL');
        if ($paths) {
            // create prefix path parts
            // Add "System" to path
            $prefixParts[] = array(
                'title' => Mage::getSingleton('admin/config')->getMenuItemLabel('system'),
            );
            // Add "Configuration" to path
            $prefixParts[] = array(
                'title' => Mage::getSingleton('admin/config')->getMenuItemLabel('system/config'),
                'url'   => $this->getUrl('adminhtml/system_config/'),
            );

            // Cycle through paths to add them to the path details
            $pathParts = $prefixParts;
            foreach ($paths as $pathData) {
                if (!array_key_exists('path', $pathData)) {
                    continue;
                }

                list($sectionName, $groupName, $fieldName) = explode('/', $pathData['path']);
                $urlParams = array('section' => $sectionName);
                if (isset($pathData['scope']) && isset($pathData['scope_id'])) {
                    switch ($pathData['scope']) {
                        case 'stores':
                            $store = Mage::app()->getStore($pathData['scope_id']);
                            if ($store) {
                                $urlParams['website'] = $store->getWebsite()->getCode();
                                $urlParams['store']   = $store->getCode();
                                $scopeLabel           = $store->getWebsite()->getName() . '/' . $store->getName();
                            }
                            break;
                        case 'websites':
                            $website = Mage::app()->getWebsite($pathData['scope_id']);
                            if ($website) {
                                $urlParams['website'] = $website->getCode();
                                $scopeLabel           = $website->getName();
                            }
                            break;
                        default:
                            break;
                    }
                }
                $adminhtmlConfig = Mage::getSingleton('adminhtml/config');
                $adminhtmlConfig->getSections();

                // Check if titles are set to prevent "[Object]" from displaying in their place
                // If Section Name is set, add it to path
                if ($sectionTitle = $adminhtmlConfig->getSystemConfigNodeLabel($sectionName)) {
                    $pathParts[] = array(
                        'title' => $sectionTitle,
                        'url'   => $this->getUrl('adminhtml/system_config/edit', $urlParams),
                    );
                }
                // If Group Name is set, add it to path
                if ($groupTitle = $adminhtmlConfig->getSystemConfigNodeLabel($sectionName, $groupName)) {
                    $pathParts[] = array(
                        'title' => $groupTitle,
                    );
                }
                // If Field Name is set, add it to path
                if ($fieldTitle = $adminhtmlConfig->getSystemConfigNodeLabel($sectionName, $groupName, $fieldName)) {
                    $pathParts[] = array(
                        'title' => $fieldTitle,
                        'scope' => $scopeLabel
                    );
                }

                $result[]  = $pathParts;
                $pathParts = $prefixParts;
            }
        }

        return $result;
    }

    /**
     * Return action url for form
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/brontoSave', array('_current' => true));
    }
}
