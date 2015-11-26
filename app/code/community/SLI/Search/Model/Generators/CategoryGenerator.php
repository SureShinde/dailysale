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
 * Generates feed category data.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_Generators_CategoryGenerator implements SLI_Search_Model_Generators_GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateForStoreId($storeId, SLI_Search_Model_Generators_GeneratorContext $generatorContext)
    {
        $logger = $generatorContext->getLogger();
        $xmlWriter = $generatorContext->getXmlWriter();

        $logger->debug(sprintf('[%s] Starting category XML generation', $storeId));

        $rootCategoryId = Mage::app()->getStore()->getRootCategoryId();
        $xmlWriter->startElement('categories');
        $xmlWriter->writeAttribute('root', $rootCategoryId);

        $processed = 0;

        $recursionLevel = 0;
        $sorted = false;
        $toLoad = false;
        $onlyActiveCategories = false;

        // Could not use the helper as we need the disabled categories
        $tree = Mage::getResourceModel('catalog/category_tree');
        /* @var $tree Mage_Catalog_Model_Resource_Category_Tree */
        $tree->loadNode($rootCategoryId)
            ->loadChildren($recursionLevel)
            ->getChildren();

        $tree->addCollectionData(null, $sorted, $rootCategoryId, $toLoad, $onlyActiveCategories);

        foreach ($tree->getCollection() as $category) {
            $this->writeCategory($xmlWriter, $category);
            ++$processed;
        }

        // categories
        $xmlWriter->endElement();

        $logger->debug(sprintf('[%s] Finished category attributes; processed items: %s', $storeId, $processed));

        return true;
    }

    /**
     * Write a single category
     *
     * @param SLI_Search_Helper_XmlWriter|XmlWriter $xmlWriter
     * @param Mage_Catalog_Model_Category           $category
     */
    protected function writeCategory(SLI_Search_Helper_XmlWriter $xmlWriter, Mage_Catalog_Model_Category $category)
    {
        $xmlWriter->startElement('category');

        $xmlWriter->writeAttribute('id', $category->getId());
        $xmlWriter->writeAttribute('name', $category->getName());
        $isActive = $category->getData('is_active');
        $xmlWriter->writeAttribute('active', (empty($isActive) || '1' != $isActive) ? '0' : '1');
        $xmlWriter->writeAttribute('parent', $category->getParentId());

        $xmlWriter->endElement();
    }
}