<?php

/**
 * Copyright (c) 2015 S.L.I. Systems, Inc. (www.sli-systems.com) - All Rights
 * Reserved
 * This file is part of Learning Search Connect.
 * Learning Search Connect is distributed under a limited and restricted
 * license � please visit www.sli-systems.com/LSC for full license details.
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
 * Generates feed price data.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_Generators_PriceGenerator implements SLI_Search_Model_Generators_GeneratorInterface
{
    protected $customerGroupMap;

    /**
     * {@inheritdoc}
     */
    public function generateForStoreId($storeId, SLI_Search_Model_Generators_GeneratorContext $generatorContext)
    {
        $logger = $generatorContext->getLogger();
        $xmlWriter = $generatorContext->getXmlWriter();

        /** @var $dataHelper SLI_Search_Helper_Data */
        $dataHelper = Mage::helper('sli_search/data');

        if (!$dataHelper->isPriceFeedEnabled($storeId)) {
            $logger->debug(sprintf('[%s] Price XML generation disabled', $storeId));

            return false;
        }

        $logger->debug(sprintf('[%s] Starting price XML generation', $storeId));

        $xmlWriter->startElement('advanced_pricing');

        $this->addPricesToFeed($storeId, $generatorContext);
        // pricing
        $xmlWriter->endElement();

        $logger->debug(sprintf('[%s] Finished writing pricing', $storeId));

        return true;
    }

    /**
     * Add the stores product prices to the feed
     *
     * @param int                                          $storeId
     * @param SLI_Search_Model_Generators_GeneratorContext $generatorContext
     */
    protected function addPricesToFeed($storeId, SLI_Search_Model_Generators_GeneratorContext $generatorContext)
    {
        $logger = $generatorContext->getLogger();
        $xmlWriter = $generatorContext->getXmlWriter();

        $logger->trace("Adding Advanced Pricing");

        // Create a map of Customer Group id > Name.
        $this->customerGroupMap = Mage::getResourceModel('customer/group_collection')->toOptionHash();

        $page = 1;
        $processed = 0;
        $pageSize = $generatorContext->getPageSize();

        /** @var $entityCollection Mage_Catalog_Model_Resource_Product_Collection */
        $entityCollection = $generatorContext->getProductCollection($storeId);

        // Loop over each page of the collections
        while ($products = $entityCollection->getItems()) {
            $logger->debug("Writing product price data...");

            /** @var $product Mage_Catalog_Model_Product */
            foreach ($products as $product) {
                $this->writeProductPriceData($product, $xmlWriter);
                ++$processed;
            }
            $logger->debug("Finished processing page: $page");

            //Break out when the number of products is less than the pagesize
            if (count($products) < $pageSize) {
                break;
            }
            $entityCollection->setPage(++$page, $pageSize);
            $entityCollection->clear();
        }

        $logger->debug("Finished adding prices for $processed products");
    }

    /**
     * Write the individual product price to the price feed.
     *
     * @param Mage_Catalog_Model_Product  $product
     * @param SLI_Search_Helper_XmlWriter $xmlWriter
     */
    protected function writeProductPriceData(Mage_Catalog_Model_Product $product, SLI_Search_Helper_XmlWriter $xmlWriter
    ) {
        $catalogPriceData = $this->getCatalogPriceRulesData($product);
        $groupPrice = $this->getPriceByType($product, 'group_price');
        $tierPrice = $this->getPriceByType($product, 'tier_price');

        if ($this->hasAdvancedPricing($catalogPriceData, $groupPrice, $tierPrice)) {
            $xmlWriter->startElement('product');
            $xmlWriter->writeNode('id', $product->getId());
            $this->writePriceData('catalog_price_rules', $catalogPriceData, $xmlWriter);
            $this->writePriceData('group_prices', $groupPrice, $xmlWriter);
            $this->writePriceData('tier_prices', $tierPrice, $xmlWriter);

            // product
            $xmlWriter->endElement();
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $priceString string to be found in the product getData call.
     *                                                - Only accepts group_price and tier_price as strings.
     *
     * @return array of tiered and grouped prices.
     */
    protected function getPriceByType(Mage_Catalog_Model_Product $product, $priceString)
    {

        $prices = $product->getData($priceString);
        if (null === $prices) {
            // Live product load - possible performance implication.
            $attribute = $product->getResource()->getAttribute($priceString);
            if ($attribute) {
                $attribute->getBackend()->afterLoad($product);
                $prices = $product->getData($priceString);
            }
        }

        // Return empty array if there are no prices.
        if(!$prices || !is_array($prices)) {
            return array();
        }

        foreach ($prices as &$price) { // & means Array passed by reference to allow editing.
            foreach ($price as $key => $value) {
                // Add customer group name to node.
                if (strcmp($key, 'cust_group') === 0) {
                    if(isset($this->customerGroupMap[$value])) {
                        $price['cust_name'] = $this->customerGroupMap[$value];
                    }
                }
            }
        }

        return $prices;
    }

    /**
     * Gets the Catalog Price Rules (CPR) for a product
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array catalog price rule data about the product if it's effected by a rule.
     */
    private function getCatalogPriceRulesData(Mage_Catalog_Model_Product $product)
    {
        /* @var $catalogRuleModel Mage_CatalogRule_Model_Rule */
        $catalogRuleModel = Mage::getModel('catalogrule/rule');

        $prices = array();
        foreach ($this->customerGroupMap as $customerGroupId => $name) {
            /* @var $session Mage_Customer_Model_Session */
            $session = Mage::getSingleton('customer/session');
            $session->setCustomerGroupId($customerGroupId);

            $finalPrice = $catalogRuleModel->calcProductPriceRule($product, $product->getPrice());

            if (!empty($finalPrice)) {
                $priceInfo = array();
                $priceInfo['final_price'] = $finalPrice;
                $priceInfo['customer_name'] = $name;
                $priceInfo['price'] = $product->getPrice();
                $prices[] = $priceInfo;
            }
        }

        return $prices;
    }

    /**
     * Were any of arrays set, and thus there is advanced pricing to apply in the export.
     *
     * @param array $catalogPriceData
     * @param array $groupPrice
     * @param array $tierPrice
     *
     * @return bool if any of the arrays are set
     */
    protected function hasAdvancedPricing($catalogPriceData, $groupPrice, $tierPrice)
    {
        return count($catalogPriceData) > 0 || count($groupPrice) > 0 || count($tierPrice) > 0;
    }

    /**
     * @param string                      $priceType name of the advanced pricing node eg 'group_prices'
     * @param array                       $priceData the actual data to output. Must be of format:
     *                                               array (
     *                                               array ( key => value ),
     *                                               ...
     *                                               array ( key => value )
     *                                               )
     * @param SLI_Search_Helper_XmlWriter $xmlWriter
     */
    protected function writePriceData($priceType, $priceData, $xmlWriter)
    {
        if (!$priceData || !is_array($priceData)) {
            return;
        }

        $xmlWriter->startElement($priceType);
        foreach ($priceData as $price) {
            $xmlWriter->startElement("price");
            foreach ($price as $key => $value) {
                $xmlWriter->writeNode($key, $value);
            }
            $xmlWriter->endElement();
        }
        $xmlWriter->endElement();
    }
}
