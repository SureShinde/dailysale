<?php

/**
 * Copyright (c) 2015 S.L.I. Systems, Inc. (www.sli-systems.com) - All Rights
 * Reserved
 * This file is part of Learning Search Connect.
 * Learning Search Connect is distributed under a limited and restricted
 * license – please visit www.sli-systems.com/LSC for full license details.
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
 * Translation and systems configuration helper
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Helper_Data extends Mage_Core_Helper_Abstract
{
    const SECTION = "sli_search/";
    const GENERAL_GROUP = "general/";
    const FEED_GROUP = "feed/";
    const FTP_GROUP = "ftp/";
    const FORM_GROUP = "form/";
    const JS_GROUP = "js/";
    const CRON_GROUP = "cron/";
    const ATTR_GROUP = "attributes/";
    const DEFAULT_ATTRS = "default_attributes/";
    const ENABLED = 1;

    /**
     * Returns true/false on whether or not the module is enabled
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isFormEnabled($storeId = 0)
    {
        $formEnabled = Mage::app()->getStore($storeId)->getConfig(
            self::SECTION . self::GENERAL_GROUP . 'form_enabled'
        );
        return (bool)($formEnabled == self::ENABLED) ? 1 : 0;
    }

    /**
     * Returns true/false on whether or not the feed is enabled
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isFeedEnabled($storeId = 0)
    {
        $feedEnabled = Mage::app()->getStore($storeId)->getConfig(
            self::SECTION . self::GENERAL_GROUP . 'feed_enabled'
        );
        return (bool)($feedEnabled == self::ENABLED) ? 1 : 0;
    }

    /**
     * Returns true/false on whether or not the price feed is enabled
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isPriceFeedEnabled($storeId = 0)
    {
        return (bool)Mage::app()->getStore($storeId)->getConfig(self::SECTION . self::GENERAL_GROUP . 'price_feed');
    }

    /**
     * Returns an integer which is the log level
     *
     * @param int $storeId
     *
     * @return int
     */
    public function getLogLevel($storeId = 0)
    {
        return (int)Mage::app()->getStore($storeId)->getConfig(self::SECTION . self::GENERAL_GROUP . 'log_level');
    }

    /**
     * Return the email setting
     *
     * @return int
     */
    public function getEmailSetting()
    {
        return Mage::getStoreConfig(self::SECTION . self::FEED_GROUP . 'email_setting');
    }

    /**
     * Return the email address of the person to send to
     *
     * @return string
     */
    public function getFeedEmail()
    {
        return Mage::getStoreConfig(self::SECTION . self::FEED_GROUP . "emailto");
    }

    /**
     * Returns true/false on whether or not we should backup feeds
     *
     * @return boolean
     */
    public function isBackupFeed()
    {
        return (bool)Mage::getStoreConfig(self::SECTION . self::FEED_GROUP . "backup");
    }

    /**
     * Returns true/false on whether or not we should drop tables
     *
     * @return boolean
     */
    public function isDataPersistent()
    {
        return (bool)Mage::getStoreConfig(self::SECTION . self::FEED_GROUP . "persistent_data");
    }

    /**
     * Returns true/false on whether or not to include out of stock items in feed
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isIncludeOutOfStockItems($storeId = 0)
    {
        return (bool)Mage::app()->getStore($storeId)->getConfig(self::SECTION . self::FEED_GROUP . "stockstatus");
    }

    /**
     * Returns batch count (alias page size)
     *
     * @return int
     */
    public function getWriteBatch()
    {
        return (int)Mage::getStoreConfig(self::SECTION . self::FEED_GROUP . "write_batch");
    }

    /**
     * Returns true/false on whether or not we should use ftp on feed generation
     *
     * @return boolean
     */
    public function isUseFtp()
    {
        return (bool)Mage::getStoreConfig(self::SECTION . self::FTP_GROUP . "enabled");
    }

    /**
     * Returns the user by which to log into the ftp server
     *
     * @return string
     */
    public function getFtpUser()
    {
        return Mage::getStoreConfig(self::SECTION . self::FTP_GROUP . "user");
    }

    /**
     * Returns the password for the user to log into the ftp server
     *
     * @return string
     */
    public function getFtpPass()
    {
        return Mage::getStoreConfig(self::SECTION . self::FTP_GROUP . "pass");
    }

    /**
     * Returns the host that we will log into via ftp
     *
     * @return string
     */
    public function getFtpHost()
    {
        return Mage::getStoreConfig(self::SECTION . self::FTP_GROUP . "host");
    }

    /**
     * Returns the path on the ftp server that we will drop the feed into
     *
     * @return string
     */
    public function getFtpPath()
    {
        return Mage::getStoreConfig(self::SECTION . self::FTP_GROUP . "path");
    }

    /**
     * Returns the javascript that goes into the html head block
     *
     * @return string
     */
    public function getHeaderJs()
    {
        return Mage::getStoreConfig(self::SECTION . self::JS_GROUP . "header");
    }

    /**
     * Returns the javascript that goes into the before_body_end
     *
     * @return string
     */
    public function getFooterJs()
    {
        return Mage::getStoreConfig(self::SECTION . self::JS_GROUP . "footer");
    }

    /**
     * Returns the javascript that goes under the mini search form to provide
     * autocomplete features from SLI
     *
     * @return string
     */
    public function getAutocompleteJs()
    {
        return Mage::getStoreConfig(self::SECTION . self::JS_GROUP . "autocomplete");
    }

    /**
     * Returns the external search domain to the SLI externally hosted search page
     *
     * @return string
     */
    public function getSearchDomain()
    {
        return Mage::getStoreConfig(self::SECTION . self::JS_GROUP . "domain");
    }

    /**
     * Returns the email to send notifications to when the cron runs
     *
     * @return string
     */
    public function getCronEmail()
    {
        return Mage::getStoreConfig(self::SECTION . self::CRON_GROUP . "email");
    }

    /**
     * Returns the frequency that the cron should run.
     *
     * @return string
     */
    public function getCronFrequency()
    {
        return Mage::getStoreConfig(self::SECTION . self::CRON_GROUP . "frequency");
    }

    /**
     * Returns the time of day that the cron should run at.
     *
     * @return string
     */
    public function getCronTime()
    {
        return Mage::getStoreConfig(self::SECTION . self::CRON_GROUP . "time");
    }

    /**
     * Checks if cron is enabled.
     *
     * @return bool
     */
    public function isCronEnabled()
    {
        return !(bool)Mage::getStoreConfig(self::SECTION . self::CRON_GROUP . "disabled");
    }

    /**
     * Returns an array of attributes configured (UI) to be included in the feed.
     *
     * @return array
     */
    public function getAttributes()
    {
        return Mage::getStoreConfig(self::SECTION . self::ATTR_GROUP . "attributes", Mage::app()->getStore()->getId());
    }

    /**
     * Return crontab formatted time for cron set time.
     *
     * @param string $frequency
     * @param array  $time
     *
     * @return string
     */
    public function getCronTimeAsCrontab($frequency, $time)
    {
        list($hours, $minutes, $ignored) = $time;
        /**
         * [0] = minutes
         * [1] = hours
         * [2] = day of month
         * [3] = month
         * [4] = day of week
         */
        $cron = array('*', '*', '*', '*', '*');

        // Parse through time
        if (!empty($minutes) || '0' === $minutes) {
            $cron[0] = (int)$minutes;
        }

        if (!empty($hours) || '0' === $hours) {
            $cron[1] = (int)$hours;
        }

        // Parse through frequencies
        switch ($frequency) {
            case SLI_Search_Model_System_Config_Source_Cron_Frequency::CRON_EVERY_HOUR:
                $cron[1] = '*/1';
                $cron[0] = '0';
                break;
            case SLI_Search_Model_System_Config_Source_Cron_Frequency::CRON_3_HOURLY:
                $cron[1] = '*/3';
                $cron[0] = '0';
                break;
            case SLI_Search_Model_System_Config_Source_Cron_Frequency::CRON_6_HOURLY:
                $cron[1] = '*/6';
                $cron[0] = '0';
                break;
            case SLI_Search_Model_System_Config_Source_Cron_Frequency::CRON_12_HOURLY:
                $cron[1] = '*/12';
                $cron[0] = '0';
                break;
        }

        return implode(' ', $cron);
    }

    /**
     * Gets the next run date based on cron settings.
     *
     * @return Zend_Date
     */
    public function getNextRunDateFromCronTime()
    {
        $now = Mage::app()->getLocale()->date();
        $frequency = $this->getCronFrequency();
        list($hours, $minutes) = explode(',', $this->getCronTime());
        $seconds = 0;

        $time = Mage::app()->getLocale()->date();
        $time->setHour(0)->setMinute(0)->setSecond(0);

        //Parse through frequencies
        switch ($frequency) {
            case SLI_Search_Model_System_Config_Source_Cron_Frequency::CRON_EVERY_HOUR:
                if ($time->compare($now) == -1) {
                    while ($time->isEarlier($now)) {
                        $time->addHour(1);
                    }
                }
                break;
            case SLI_Search_Model_System_Config_Source_Cron_Frequency::CRON_3_HOURLY:
                if ($time->compare($now) == -1) {
                    while ($time->isEarlier($now)) {
                        $time->addHour(3);
                    }
                }
                break;
            case SLI_Search_Model_System_Config_Source_Cron_Frequency::CRON_6_HOURLY:
                if ($time->compare($now) == -1) {
                    while ($time->isEarlier($now)) {
                        $time->addHour(6);
                    }
                }
                break;
            case SLI_Search_Model_System_Config_Source_Cron_Frequency::CRON_12_HOURLY:
                if ($time->compare($now) == -1) {
                    while ($time->isEarlier($now)) {
                        $time->addHour(12);
                    }
                }
                break;
            case SLI_Search_Model_System_Config_Source_Cron_Frequency::CRON_DAILY:
                $time->setHour($hours)->setMinute($minutes)->setSecond($seconds);
                if ($time->compare($now) == -1) {
                    $time->addDay(1);
                }
                break;
        }

        return $time;
    }

    /**
     * Return a float of current Unix timestamp with microseconds
     *
     * @return float
     */
    public function mtime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * Returns domain(s) as a js var
     *
     * @return string
     */
    public function getSLIDomainJs()
    {
        $searchURL = $this->getSearchDomain();
        $scheme = parse_url($searchURL, PHP_URL_SCHEME);
        if (!$scheme) {
            $searchURL = "http://" . $searchURL;
        }
        preg_match('/http(s|):\/\/(.+?)\//', $searchURL, $matches);
        $searchURLBase = $searchURL;
        if (isset($matches[2])) {
            $searchURLBase = $matches[2];
        }
        $returnJS = "\n<script type=\"text/javascript\">\nvar slibaseurlsearch = '" . $searchURL
            . "';\nvar slibaseurl = '" . $searchURLBase . "';\n</script>\n";

        return $returnJS;
    }


    /**
     * Checks to see if the the custom form code should be used
     *
     * @return bool
     */
    public function useCustomForm()
    {
        return (bool)Mage::getStoreConfig(self::SECTION . self::FORM_GROUP . "customform");
    }


    /**
     * This returns the form code from the DB
     *
     * @return string
     */
    public function getFormData()
    {
        return Mage::getStoreConfig(self::SECTION . self::FORM_GROUP . "formcode");
    }

    /**
     * Render the cart grand total and total item within the cart
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    protected function renderCartTotal($quote)
    {
        if (!$quote) {
            return false;
        }

        //Declare the array container
        $cartInfoArray = array();
        $quoteItemCount = intval($quote->getItemsQty());

        //Store the item count to array
        $cartInfoArray['NumberOfItems'] = $quoteItemCount;

        $totals = $quote->getTotals();
        if ($totals) {
            if (isset($totals['grand_total'])) {
                $cartInfoArray['TotalPrice'] = $this->formatCurrency($totals['grand_total']->getValue());
            }

            if (isset($totals['tax'])) {
                $cartInfoArray['TotalTax'] = $this->formatCurrency($totals['tax']->getValue());
            }
        }

        //Get The Cart Total Discount Amount
        $items = $quote->getAllVisibleItems();
        $itemDiscount = 0;
        foreach ($items as $item) {
            if (!$item) {
                continue;
            }
            $itemDiscount += $item->getDiscountAmount();
        }
        $cartInfoArray['TotalDiscount'] = $this->formatCurrency($itemDiscount);

        //Get The Delivery Cost if applicable
        $shippingCost = $quote->getShippingAddress()->getShippingAmount();
        $shippingCostTax = $quote->getShippingAddress()->getShippingTaxAmount();
        if ($shippingCost == (float)0) {
            $cartInfoArray['DeliveryCost'] = $this->formatCurrency(0);
        } else {
            $cartInfoArray['DeliveryCost'] = $this->formatCurrency((float)$shippingCost + (float)$shippingCostTax);
        }

        return $cartInfoArray;
    }

    /**
     * Render the cart item detail
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    protected function renderItemsDetail($quote)
    {
        //Array of items
        $itemsArray = array();
        if (!$quote) {
            return false;
        }

        $items = $quote->getAllVisibleItems();

        foreach ($items as $item) {
            /** @var $item Mage_Sales_Model_Quote_Item */
            if (!$item) {
                continue;
            }

            //Declare an array to store item information
            $itemInfo = array();
            /** @var $itemProduct Mage_Catalog_Model_Product */
            $itemProduct = $item->getProduct();

            $itemInfo['title'] = $item->getName();
            $itemInfo['sku'] = $item->getSku();
            $itemInfo['qty'] = intval($item->getQty());
            //Get the item Product Object
            $product = $item->getProduct();
            //Get the original price for item product
            $itemInfo['price'] = $this->formatCurrency($product->getPrice());
            //Get the sale price
            $itemInfo['sale_price'] = $this->formatCurrency($item->getPriceInclTax());

            $itemInfo['item_url'] = $this->getItemUrl($item);
            $itemInfo['remove_url'] = Mage::getUrl('checkout/cart/delete/', array('id' => $item->getId()));
            $itemInfo['image_url'] = Mage::getModel('catalog/product_media_config')->getMediaUrl(
                $itemProduct->getThumbnail()
            );

            $itemsArray[] = $itemInfo;
        }
        return $itemsArray;
    }

    /**
     * Get the item url
     *
     * @param $item Mage_Sales_Model_Quote_Item
     *
     * @return string
     */
    public function getItemUrl($item)
    {
        if ($item->getRedirectUrl()) {
            return $item->getRedirectUrl();
        }

        $product = $item->getProduct();
        $option = $item->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }
        return $product->getUrlModel()->getUrl($product);
    }

    /**
     * Render the JSONP object for SLI
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return string
     */
    public function getCartJSONP($quote)
    {
        $keyValues['form_key'] = Mage::getSingleton('core/session')->getFormKey();
        $keyValues['logged_in'] = Mage::getSingleton('customer/session')->isLoggedIn();
        $keyValues['user_name'] = $this->escapeHtml(Mage::getSingleton('customer/session')->getCustomer()->getName());

        $cart = $this->renderCartTotal($quote);
        $items['items'] = $this->renderItemsDetail($quote);

        $result = array_merge($keyValues, $cart, $items);
        $jsonResult = json_encode($result);
        //Wrap up as jsonp object
        $jsonpResult = "sliCartRequest($jsonResult)";
        return $jsonpResult;
    }

    /**
     * Return whether an email should be sent or not
     *
     * @param $error
     *
     * @return bool
     */
    public function sendEmail($error)
    {
        $emailLevel = $this->getEmailSetting();
        if ($emailLevel == 3) {
            return true;
        } else {
            if ($emailLevel == 2 && isset($error) && $error) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Currency formatting
     *
     * @param $val
     *
     * @return string
     */
    public function formatCurrency($val)
    {
        return Mage::helper('checkout')->getQuote()->getStore()->formatPrice($val, false);
    }
}
