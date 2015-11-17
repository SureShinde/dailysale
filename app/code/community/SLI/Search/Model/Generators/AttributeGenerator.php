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
 * Generates feed attribute data.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_Generators_AttributeGenerator implements SLI_Search_Model_Generators_GeneratorInterface
{
    //TODO: what is this supposed to do?
    protected $indexValueAttributes = array();
    protected $attributeValues = array();

    /**
     * {@inheritdoc}
     */
    public function generateForStoreId($storeId, SLI_Search_Model_Generators_GeneratorContext $generatorContext)
    {
        $logger = $generatorContext->getLogger();
        $xmlWriter = $generatorContext->getXmlWriter();

        $logger->debug(sprintf('[%s] Starting attribute XML generation', $storeId));

        $this->initAttributes($storeId, $logger);

        $xmlWriter->startElement('attributes');

        if ($this->attributeValues) {
            $xmlWriter->writeAttributes($this->attributeValues);
        }

        // attributes
        $xmlWriter->endElement();

        $logger->debug(sprintf('[%s] Finished writing attributes', $storeId));

        return true;
    }

    /**
     * Create the attribute values for a store
     *
     * @param int                          $storeId
     * @param SLI_Search_Helper_FeedLogger $logger
     */
    protected function initAttributes($storeId, SLI_Search_Helper_FeedLogger $logger)
    {
        /** @var $feedHelper SLI_Search_Helper_Feed */
        $feedHelper = Mage::helper('sli_search/feed');
        $extraAttributes = $feedHelper->getExtraAttributes();

        /** @var $attributeCollection Mage_Catalog_Model_Resource_Product_Attribute_Collection */
        $attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection');
        $attributeCollection->addStoreLabel($storeId);

        /** @var $attribute Mage_Eav_Model_Entity_Attribute */
        foreach ($attributeCollection as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            // Only add the options that we require
            if (in_array($attributeCode, $extraAttributes)) {
                $attributeOptions = $this->getAttributeOptions($attribute, $storeId, $logger);
                // Only add the attributes that have options
                if ($attributeOptions) {
                    $this->attributeValues[$attributeCode] = $attributeOptions;
                }
            }
        }
    }

    /**
     * Returns attributes all values in label-value or value-value pairs form. Labels are lower-cased.
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @param                                 $storeId
     * @param SLI_Search_Helper_FeedLogger    $logger
     *
     * @return array
     */
    protected function getAttributeOptions($attribute, $storeId, SLI_Search_Helper_FeedLogger $logger)
    {
        $options = array();

        if ($attribute->usesSource()) {
            // should attribute have index (option value) instead of a label?
            $index = in_array($attribute->getAttributeCode(), $this->indexValueAttributes) ? 'value' : 'label';

            try {
                foreach ($attribute->getSource()->getAllOptions() as $option) {
                    foreach (is_array($option['value']) ? $option['value'] : array($option) as $innerOption) {
                        if (strlen($innerOption['value'])) {
                            // skip ' -- Please Select -- ' option
                            $options[$innerOption['value']] = $innerOption[$index];
                        }
                    }
                }
            } catch (\Exception $e) {
                // ignore exceptions connected with source models
                $logger->trace(
                    sprintf(
                        '[%s] Failed to get attribute options: %s',
                        $storeId, $e->getMessage()
                    ),
                    array('exception' => $e)
                );
            }
        }

        return $options;
    }
}