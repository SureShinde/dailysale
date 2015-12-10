<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Email_Import extends Mage_Adminhtml_Block_System_Email_Template
{
    /**
     * Set transactional emails grid template
     */
    protected function _construct()
    {
        Mage_Adminhtml_Block_Template::_construct();
        $this->setTemplate('bronto/email/template/list.phtml');
    }

    /**
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            return parent::_prepareLayout();
        }

        $this->setChild('back_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'   => Mage::helper('adminhtml')->__('Back'),
                    'onclick' => "window.location='{$this->getBackUrl()}'",
                    'class'   => 'scalable back',
                ))
        );

        $this->setChild('import_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'   => Mage::helper('adminhtml')->__('Load Default Magento Templates'),
                    'onclick' => "window.location='{$this->getLoadDefaultsUrl()}'",
                    'class'   => 'go'
                ))
        );

        $this->setChild('grid', $this->getLayout()->createBlock('bronto_email/adminhtml_system_email_import_grid', 'email.import.grid'));

        return Mage_Adminhtml_Block_Template::_prepareLayout();
    }

    /**
     * Get transactional emails page header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (!Mage::helper('bronto_email')->isEnabledForAny()) {
            return parent::getHeaderText();
        }

        return Mage::helper('bronto_email')->__('Import Magento Transactional Email Templates');
    }

    /**
     * Get URL to import existing email templates
     *
     * @return string
     */
    public function getLoadDefaultsUrl()
    {
        return $this->getUrl('*/system_email_template/loadDefaults');
    }

    /**
     * Get URL to go back
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/system_email_template/index');
    }

    /**
     * Get link to transactional email configuration
     *
     * @return string
     */
    public function getConfigLink()
    {
        $url = $this->getUrl('/system_config/edit/section/bronto_email');

        return '<strong>System &rsaquo; Configuration &rsaquo; Bronto &raquo; <a href="' . $url . '" title="Transactional Emails">Transactional Emails</a></strong>';
    }
}
