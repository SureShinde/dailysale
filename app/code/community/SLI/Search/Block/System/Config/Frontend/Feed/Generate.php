<?php

/**
 * Copyright (c) 2015 S.L.I. Systems, Inc. (www.sli-systems.com) - All Rights
 * Reserved
 * This file is part of Learning Search Connect.
 * Learning Search Connect is distributed under a limited and restricted
 * license - please visit www.sli-systems.com/LSC for full license details.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE. TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, IN NO
 * EVENT WILL SLI BE LIABLE TO YOU OR ANY OTHER PARTY FOR ANY GENERAL, DIRECT,
 * INDIRECT, SPECIAL, INCIDENTAL OR CONSEQUENTIAL LOSS OR DAMAGES OF ANY
 * CHARACTER ARISING OUT OF THE USE OF THE CODE AND/OR THE LICENSE INCLUDING
 * BUT NOT LIMITED TO PERSONAL INJURY, LOSS OF DATA, LOSS OF PROFITS, LOSS OF
 * ASSIGNMENTS, DATA OR OUTPUT FROM THE SERVICE BEING RENDERED INACCURATE,
 * FAILURE OF CODE, SERVER DOWN TIME, DAMAGES FOR LOSS OF GOODWILL, BUSINESS
 * INTERRUPTION, COMPUTER FAILURE OR MALFUNCTION, OR ANY AND ALL OTHER DAMAGES
 * OR LOSSES OF WHATEVER NATURE, EVEN IF SLI HAS BEEN INFORMED OF THE
 * POSSIBILITY OF SUCH DAMAGES.
 */

/**
 * Source renderer for product attributes data.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Block_System_Config_Frontend_Feed_Generate extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_buttonId = "generate_feed_button";

    /**
     * Programmatically include the generate feed javascript in the adminhtml
     * JS block.
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        $block = $this->getLayout()->createBlock("sli_search/system_config_frontend_feed_generate_js");
        $block->setData("button_id", $this->_buttonId);

        $this->getLayout()->getBlock('js')->append($block);
        return parent::_prepareLayout();
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $button = $this->getButtonHtml();

        $notice = "";
        if ($this->feedGenIsLocked()) {
            $notice = "<p id='sli_display_msg' class='note'>" . Mage::helper("sli_search/feed")->getAjaxNotice()
                . "</p>";
        }
        return $button . $notice;
    }

    /**
     * Generate button html for the feed button
     *
     * @return string
     */
    public function getButtonHtml()
    {
        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(
                array(
                    'id'      => $this->_buttonId,
                    'label'   => $this->helper('sli_search')->__('Generate Feed'),
                    'onclick' => 'javascript:sliSearch.generateFeed(); return false;'
                )
            );

        if ($this->feedGenIsLocked()) {
            $button->setDisabled(true);
        }

        return $button->toHtml();
    }

    /**
     * Check to see if there are any locks for any feeds at the moment
     *
     * @return boolean
     */
    protected function feedGenIsLocked()
    {
        return Mage::helper('sli_search/feed')->feedLocksExist();
    }
}
