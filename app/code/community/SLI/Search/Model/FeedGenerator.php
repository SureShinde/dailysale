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
 * Generates feed file based on store
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_FeedGenerator
{
    /**
     * Generate feed(s) for the given store id.
     *
     * @param int                          $storeId
     * @param SLI_Search_Helper_FeedLogger $logger
     *
     * @return array List of stati for each feed file.
     */
    public function generateForStoreId($storeId, SLI_Search_Helper_FeedLogger $logger)
    {
        // just to be safe
        Mage::app()->setCurrentStore($storeId);

        /** @var SLI_Search_Helper_Feed $feedHelper */
        $feedHelper = Mage::helper('sli_search/feed');
        /** @var SLI_Search_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('sli_search/data');

        /** @var $xmlWriter SLI_Search_Helper_XmlWriter */
        $xmlWriter = Mage::helper('sli_search/xmlWriter');

        /** @var SLI_Search_Model_Generators_GeneratorContext $generatorContext */
        $generatorContext = Mage::getModel('sli_search/generators_GeneratorContext');
        $generatorContext->init($xmlWriter, $logger, $dataHelper->getWriteBatch());

        $generators = array(
            Mage::getModel('sli_search/generators_MetaGenerator'),
            Mage::getModel('sli_search/generators_ProductGenerator'),
            Mage::getModel('sli_search/generators_AttributeGenerator'),
            Mage::getModel('sli_search/generators_CategoryGenerator'),
            Mage::getModel('sli_search/generators_PriceGenerator'),
        );

        $logger->trace("Creating catalog feed.....");

        $feedFilename = $feedHelper->getFeedFile($storeId);
        $xmlWriter->openFeed($feedFilename);

        /** @var $generator SLI_Search_Model_Generators_GeneratorInterface */
        foreach ($generators as $generator) {
            if ($generator) {
                $generator->generateForStoreId($storeId, $generatorContext);
            }
        }

        $xmlWriter->closeFeed();

        $logger->trace("Finished creating catalog feed");

        return true;
    }
}
