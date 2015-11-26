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
 * Search Mini Form block.
 * Provides rendering abilities for SLI version of the form.mini.phtml that
 * replaces the search url with an external url to an SLI hosted search page.
 * Provides an inline search autocomplete feature as well.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Block_Search_Form_Mini extends Mage_Core_Block_Template
{
    /**
     * Returns SLI provided auto complete javascript.
     *
     * @return string
     */
    public function getInlineAutocompleteJs()
    {
        return Mage::helper('sli_search')->getAutocompleteJs();
    }

    /**
     * Returns External search domain to the search page hosted by SLI.
     *
     * @return string
     */
    public function getSearchUrl()
    {
        $url = Mage::helper('sli_search')->getSearchDomain();
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!$scheme) {
            $url = "http://" . $url;
        }

        return $url;
    }

    /**
     * Retrieve the form code from the database for this site
     *
     * @return string
     */
    public function getFormData()
    {
        return $data = Mage::helper('sli_search')->getFormData();
    }

    /**
     * Use the default SLI layout or the custom layout from the DB
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (Mage::helper('sli_search')->useCustomForm()) {
            return $this->getFormData();
        } else {
            $this->setTemplate('sli/search/form.mini.phtml');
        }
        return parent::_toHtml();
    }
}
