<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Common_Block_Adminhtml_System_Config_Guide extends Mage_Adminhtml_Block_Template
{
    /**
     * Array of possible sections
     *
     * @var array
     */
    private $_sections = array(
        'bronto',
        'bronto_api',
        'bronto_common',
        'bronto_news',
        'bronto_newsletter',
        'bronto_customer',
        'bronto_order',
        'bronto_reminder',
        'bronto_email',
        'bronto_verify',
        'bronto_coupon',
        'bronto_popup',
        'bronto_product',
        'bronto_reviews',
    );

    /**
     * Function to match query section against current section
     *
     * @param $section
     *
     * @return bool
     */
    public function isBrontoSection($section)
    {
        return $this->getCurrentSection() == $section;
    }

    /**
     * Get code for child html block based on current section
     *
     * @return string
     */
    public function getSectionChildHtml()
    {
        $section = $this->getCurrentSection();
        if (in_array($section, $this->_sections)) {
            return $section . '_guide';
        }

        return false;
    }

    /**
     * Get Current section from request params
     *
     * @return mixed
     */
    public function getCurrentSection()
    {
        $section = Mage::app()->getRequest()->getParam('section', false);
        if ($section == 'bronto') {
            $section = 'bronto_common';
        }

        return $section;
    }

    /**
     * Get Url for Ajax call to toggle displaying guide for current section
     *
     * @return mixed
     */
    public function getToggleUrl()
    {
        return Mage::helper("adminhtml")->getUrl('*/guiders/toggle');
    }

    /**
     * Determine if guide should be shown
     *
     * @param bool|string $section
     *
     * @return bool
     */
    public function canShowGuide($section = false)
    {
        if (!$section) {
            $section = $this->getCurrentSection();
        }

        $canShow = Mage::helper('bronto_common')->getAdminScopedConfig($section . '/guide/display');

        return ($canShow == '0') ? false : true;
    }

    /**
     * Determine if Currently in Default Scope
     *
     * @return bool
     */
    public function isDefaultScope()
    {
        $scopeParams = Mage::helper('bronto_common')->getScopeParams();

        return ($scopeParams['scope'] == 'default');
    }
}
