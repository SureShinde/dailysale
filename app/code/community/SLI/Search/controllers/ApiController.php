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
 * Provide the URL for SLI to retrieve the current shopping cart items and all relative information
 * An JSON file will be passed back
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_ApiController extends Mage_Core_Controller_Front_Action
{
    // Declare the quote
    private $quote = null;

    /**
     * Load the quote
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function getQuote()
    {
        if ($this->quote) {
            return $this->quote;
        } else {
            return Mage::getSingleton('checkout/session')->getQuote();
        }
    }

    /**
     * Allow SLI to call this url to get the cart jsonp object
     * uri: /sli/api/cart
     */
    public function cartAction()
    {
        $jsonpResult = Mage::helper('sli_search')->getCartJSONP(
            $this->getQuote()
        );

        if ($jsonpResult) {
            $this->getResponse()->setBody($jsonpResult);
        }
    }
}
