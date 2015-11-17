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
 * Generates feed product data.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_Generators_ProductGenerator implements SLI_Search_Model_Generators_GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateForStoreId($storeId, SLI_Search_Model_Generators_GeneratorContext $generatorContext)
    {
        $logger = $generatorContext->getLogger();
        $xmlWriter = $generatorContext->getXmlWriter();

        $logger->debug(sprintf('[%s] Starting product XML generation', $storeId));

        $xmlWriter->startElement('products');

        $this->addProductsToFeed($storeId, $generatorContext);

        $xmlWriter->endElement();

        $logger->debug(sprintf('[%s] Finished writing products', $storeId));

        return true;
    }

    /**
     * Add the stores products to the feed with the selected attributes
     *
     * @param int                                          $storeId
     * @param SLI_Search_Model_Generators_GeneratorContext $generatorContext
     */
    protected function addProductsToFeed($storeId,
        SLI_Search_Model_Generators_GeneratorContext $generatorContext
    ) {
        $logger = $generatorContext->getLogger();
        $xmlWriter = $generatorContext->getXmlWriter();

        $logger->trace("Adding products");

        /** @var $feedHelper SLI_Search_Helper_Feed */
        $feedHelper = Mage::helper('sli_search/feed');
        $extraAttributes = $feedHelper->getExtraAttributes();

        $page = 1;
        $processed = 0;
        $pageSize = $generatorContext->getPageSize();

        // might need to iterate over more than one product collection...
        $productCollections = array(
            'mainProducts' => array(
                'collection' => $generatorContext->getProductCollection($storeId),
                'filters' => array(),
            )
        );
        /** @var $dataHelper SLI_Search_Helper_Data */
        $dataHelper = Mage::helper('sli_search/data');

        if ($dataHelper->isIncludeOutOfStockItems($storeId)) {
            /** @var Mage_Catalog_Model_Resource_Product_Collection $outOfStockProductsCollections */
            $outOfStockProductsCollection = $generatorContext->getRawProductCollection($storeId);
            $productCollections['outOfStockProducts'] = array(
                'collection' => $outOfStockProductsCollection,
                'filters'     => array(
                    function ($product) {
                        /** @var Mage_Catalog_Model_Product $product */
                        /** @var Varien_Object $value */
                        $value = $product->getData('stock_item');
                        return (bool) !$value->getData('is_in_stock');
                    }
                )
            );
        }

        // Add in the rating data if one of the attributes is selected
        $reviewAttributes = array('review_reviews_count', 'review_rating_summary');
        /** @var $review Mage_Review_Model_Review */
        $review = array_intersect($reviewAttributes, $extraAttributes) ? Mage::getModel('review/review') : null;

        foreach ($productCollections as $collectionKey => $productCollectionDetails) {
            /** @var Mage_Catalog_Model_Resource_Product_Collection $productCollection */
            $productCollection = $productCollectionDetails['collection'];
            $productFilters = $productCollectionDetails['filters'];

            $logger->debug(sprintf('Writing products for collection %s...', $collectionKey));

            while ($products = $productCollection->getItems()) {
                if ($review) {
                    $logger->debug("Adding review data for page: $page");
                    $review->appendSummary($productCollection);
                }

                // apply include filters
                $filteredProducts = array_filter($products, function ($product)  use ($productFilters) {
                    if (!$productFilters) {
                        // no filter - use all
                        return true;
                    }

                    // got filters, so lets see if at least one wants the product...
                    foreach ($productFilters as $includeFilter) {
                        if ($includeFilter($product)) {
                            return true;
                        }
                    }

                    return false;
                });

                /** @var $product Mage_Catalog_Model_Product */
                foreach ($filteredProducts as $product) {
                    // load category_ids into product _data
                    $product->getCategoryIds();
                    $this->addProductRelationshipData($product, $extraAttributes);
                    $this->writeProductData($product, $xmlWriter);
                    ++$processed;
                }
                $logger->debug(sprintf('"Finished processing product page %s for collection %s', $page, $collectionKey));

                // break out when we get less than a full page
                if (count($products) < $pageSize) {
                    break;
                }

                $productCollection->setPage(++$page, $pageSize);
                $productCollection->clear();
            }
        }

        $logger->debug("Finished adding $processed products");
    }

    /**
     * Write the individual product to the feed.
     *
     * @param Mage_Catalog_Model_Product  $product
     * @param SLI_Search_Helper_XmlWriter $xmlWriter
     */
    protected function writeProductData(Mage_Catalog_Model_Product $product, SLI_Search_Helper_XmlWriter $xmlWriter)
    {
        $xmlWriter->startElement('product');
        $xmlWriter->writeAttribute('id', $product->getId());
        $xmlWriter->writeAttribute('sku', $product->getSku());

        foreach ($product->getData() as $name => $value) {
            if ('rating_summary' == $name) {
                // Always add all review data if it is in the collection
                /** @var $value Mage_Review_Model_Review_Summary */
                $xmlWriter->startElement('reviews');
                $xmlWriter->writeAttribute('count', $value->getData('reviews_count'));
                $xmlWriter->writeAttribute('summary', $value->getData('rating_summary'));
                $xmlWriter->endElement();
            } else {
                if ('stock_item' == $name) {
                    /** @var $value Varien_Object */
                    $xmlWriter->writeNode('is_in_stock', $value->getData('is_in_stock'));
                } else {
                    $xmlWriter->writeNode($name, $value);
                }
            }
        }

        $type = $product->getTypeInstance();
        if ($childrenIds = $type->getChildrenIds($product->getId(), true)) {
            // Add the children to the feed. Grouped product required items are in the different groups.
            // See getChildrenIds
            foreach ($childrenIds as $group => $groupIdChildren) {
                $xmlWriter->startElement('child_ids');
                $xmlWriter->writeAttribute('group', $group);
                foreach ($groupIdChildren as $childId) {
                    $xmlWriter->writeElement('id', $childId);
                }
                $xmlWriter->endElement();
            }
        }

        // product
        $xmlWriter->endElement();
    }

    /**
     * Adds the id's for inter product linking based on up sells, cross sells or related products
     * Only doing so if they are added as extra attributes
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string                     $extraAttributes
     */
    protected function addProductRelationshipData($product, $extraAttributes)
    {
        // Array of XML Node Name => Product Object function call which obtains the ids for each relationship type.
        $linkedAttributesFunctionMap = array(
            'linked_related'   => 'getRelatedProductIds',
            'linked_upsell'    => 'getUpsellProductIds',
            'linked_crosssell' => 'getCrossSellProductIds'
        );

        foreach ($linkedAttributesFunctionMap as $label => $method) {
            if (in_array($label, $extraAttributes)) {
                /* @var $ids array */
                $ids = $product->{$method}();
            }
        }
    }
}