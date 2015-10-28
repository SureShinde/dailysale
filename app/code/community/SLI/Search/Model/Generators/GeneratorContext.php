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
 * Generator context.
 * This is the place to share things between generators.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_Generators_GeneratorContext
{
    /** @var  Mage_Catalog_Model_Resource_Product_Collection $productCollection */
    protected $productCollection;
    /** @var  SLI_Search_Helper_FeedLogger $logger */
    protected $logger;
    /** @var SLI_Search_Helper_XmlWriter $xmlWriter */
    protected $xmlWriter;
    protected $pageSize;

    /**
     * Create new instance.
     */
    public function __construct()
    {
        $this->productCollection = null;
    }

    /**
     * Init.
     *
     * @param SLI_Search_Helper_XmlWriter  $xmlWriter
     * @param SLI_Search_Helper_FeedLogger $logger
     * @param int                          $pageSize
     */
    public function init(SLI_Search_Helper_XmlWriter $xmlWriter, SLI_Search_Helper_FeedLogger $logger, $pageSize)
    {
        $this->logger = $logger;
        $this->xmlWriter = $xmlWriter;
        $this->pageSize = $pageSize;
    }

    /**
     * @return SLI_Search_Helper_FeedLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return SLI_Search_Helper_XmlWriter
     */
    public function getXmlWriter()
    {
        return $this->xmlWriter;
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * Get the product collection.
     *
     * The collection will be set up with the widest required set of attributes/prices/etc.
     * That means if there are new, feature-flagged options this is the place to change things.
     *
     * @param $storeId
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getProductCollection($storeId)
    {
        $logger = $this->logger;

        /** @var $dataHelper SLI_Search_Helper_Data */
        $dataHelper = Mage::helper('sli_search/data');

        $page = 1;
        $pageSize = $this->pageSize;

        // keep at most one collection at a time as we do iterate over stores and do not need them any more
        if ($this->productCollection && $this->productCollection->getStoreId() == $storeId) {
            $logger->debug("Recycling product collection with page size: $pageSize...");
            $this->productCollection->setPage($page, $pageSize);
            $this->productCollection->clear();

            return $this->productCollection;
        }

        $logger->debug(sprintf('[%s] Creating context product collection with page size: %s...', $storeId, $pageSize));

        /** @var $entityCollection Mage_Catalog_Model_Resource_Product_Collection */
        $this->productCollection = $this->getRawProductCollection($storeId);
        // add price data only here as otherwise the raw collection will not see out of stock items
        $this->productCollection
            ->addPriceData();

        // these are handled in the product generator as adding price data will drop them from the collection
        if (!$dataHelper->isIncludeOutOfStockItems($storeId)) {
            // lets still add this here in case Magentos (internal) behaviour changes in the future
            $logger->debug(sprintf('[%s] Exclude out of stock items', $storeId));
            /** @var Mage_CatalogInventory_Model_Stock $stockHelper */
            $stockHelper = Mage::getModel('cataloginventory/stock');
            $stockHelper->addInStockFilterToCollection($this->productCollection);
        } else {
            $logger->debug(sprintf('[%s] Include out of stock items', $storeId));
        }

        if ($dataHelper->isPriceFeedEnabled($storeId)) {
            $logger->debug("Adding tier price data...");
            $this->productCollection
                ->addTierPriceData();
        }

        $logger->debug("Finished creating collection; select: " . $this->productCollection->getSelect()->__toString());

        return $this->productCollection;
    }

    /**
     * This is not cached and will create a new collection on each call.
     *
     * @param int $storeId
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getRawProductCollection($storeId)
    {
        $logger = $this->logger;

        $page = 1;
        $pageSize = $this->pageSize;

        $logger->debug("Creating raw product collection with page size: $pageSize...");

        /** @var $feedHelper SLI_Search_Helper_Feed */
        $feedHelper = Mage::helper('sli_search/feed');
        $extraAttributes = $feedHelper->getExtraAttributes();

        /** @var $entityCollection Mage_Catalog_Model_Resource_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection');

        $logger->debug("Adding product attributes: " . implode(',', $extraAttributes));
        $productCollection
            ->setPage($page, $pageSize)
            ->addAttributeToSelect($extraAttributes)
            ->addAttributeToFilter('status', 1);
        $productCollection
            ->addStoreFilter($storeId);

        return $productCollection;
    }
}