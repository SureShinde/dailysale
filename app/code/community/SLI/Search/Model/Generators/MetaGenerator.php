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
 * Generates feed meta data.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_Generators_MetaGenerator implements SLI_Search_Model_Generators_GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateForStoreId($storeId, SLI_Search_Model_Generators_GeneratorContext $generatorContext)
    {
        $logger = $generatorContext->getLogger();
        $xmlWriter = $generatorContext->getXmlWriter();

        $logger->debug(sprintf('[%s] Starting meta XML generation', $storeId));

        $xmlWriter->startElement('meta');
        $xmlWriter->writeAttribute('storeId', $storeId);

        $modules = Mage::getConfig()->getNode('modules')->children();
        $moduleInfo = $modules->SLI_Search->asArray();
        $lscVersion = isset($moduleInfo['version']) ? $moduleInfo['version'] : 'UNKNOWN';
        $magentoVersion = Mage::getVersion();

        $xmlWriter->writeElement('lscVersion', $lscVersion);
        $xmlWriter->writeElement('magentoVersion', $magentoVersion);
        $xmlWriter->writeElement('context', 'cli' == php_sapi_name() ? 'CLI' : 'UI');
        $xmlWriter->writeElement('baseUrl', Mage::getBaseUrl());

        $created = new DateTime();
        $xmlWriter->writeElement('created', $created->format(DateTime::ISO8601));

        /** @var $feedHelper SLI_Search_Helper_Feed */
        $feedHelper = Mage::helper('sli_search/feed');
        $extraAttributes = $feedHelper->getExtraAttributes();
        $xmlWriter->writeElement('extraAttributes', implode(', ', array_values($extraAttributes)));

        // meta
        $xmlWriter->endElement();

        $logger->debug(sprintf('[%s] Finished writing meta', $storeId));

        return true;
    }
}
