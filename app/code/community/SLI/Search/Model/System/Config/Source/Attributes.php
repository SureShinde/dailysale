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
 * Source renderer for product attributes data.
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_System_Config_Source_Attributes
{
    /**
     * Remove attributes that do not need to be included in the feed or cause issues
     * in feed generation when selected.
     * This should be done in Feed::$defaultRequiredAttributes; not sure about this one so we'll leave it for now
     *
     * @var array
     */
    protected $blockedAttributes = array('category_id');

    /**
     * We want these attributes to appear in the drop down configuration menu, but they are not included in the
     * EAV selection. We must add them in individually.
     * If a key exists for a value, that key will be used as the label instead of the prefixed value
     *
     * @var array
     */
    protected $nonEavAttributes
        = array(
            'max_price',
            'min_price',
            'related_products'   => 'linked_related',
            'upsell_products'    => 'linked_upsell',
            'crosssell_products' => 'linked_crosssell'
        );

    /**
     * Prefix to use in the dropdown to differentiate the inventory attributes
     */
    const LINKED_PRODUCTS_PREFIX = 'linked';

    /**
     * Prefix to use in the dropdown to differentiate the inventory attributes
     */
    const INVENTORY_ATTRIBUTES_PREFIX = 'inventory';

    /**
     * Attributes from the flat inventory table that we will use for the feed
     * if a key exists for a value, that key will be used as the label instead of the prefixed value
     *
     * @var array
     */
    protected $_inventoryAttributes
        = array(
            'qty',
            'is_in_stock',
            'manage_stock',
            'backorders',
        );

    /**
     * Prefix to use in the dropdown to differentiate the review attributes
     */
    const REVIEW_ATTRIBUTES_PREFIX = 'review';

    /**
     * Attributes from customer review that we will use for the feed
     * if a key exists for a value, that key will be used as the label instead of the prefixed value
     *
     * @var array
     */
    protected $reviewAttributes
        = array(
            'reviews_count' => 'reviews_count',
            'rating_summary',
        );

    /**
     * Returns code => code pairs of attributes for all product attributes
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var SLI_Search_Helper_Feed $feedHelper */
        $feedHelper = Mage::helper('sli_search/feed');
        $defaultRequiredAttributes = $feedHelper->getDefaultRequiredAttributes();

        $productEntityId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getId();

        $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter($productEntityId);
        $attributes->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns(array("code" => 'attribute_code'));

        foreach ($attributes as $attribute) {
            $code = $attribute['code'];
            if (!in_array($attribute['code'], $defaultRequiredAttributes)
                && !in_array($attribute['code'], $this->blockedAttributes)
            ) {
                $options[$code] = $code;
            }
        }

        // We want some non-eav attributes to be added to this dropdown as well
        foreach ($this->nonEavAttributes as $label => $attributeCode) {
            $label = is_string($label) ? $label : $attributeCode;
            $options[$attributeCode] = $label;
        }

        // Add the inventory attributes
        foreach ($this->_inventoryAttributes as $label => $attributeCode) {
            $code = self::INVENTORY_ATTRIBUTES_PREFIX . "_" . $attributeCode;
            $label = is_string($label) ? $label : self::INVENTORY_ATTRIBUTES_PREFIX . "_" . $attributeCode;
            $options[$code] = $label;
        }

        // Add the review attributes
        foreach ($this->reviewAttributes as $label => $attributeCode) {
            $code = self::REVIEW_ATTRIBUTES_PREFIX . "_" . $attributeCode;
            $label = is_string($label) ? $label : self::REVIEW_ATTRIBUTES_PREFIX . "_" . $attributeCode;
            $options[$code] = $label;
        }

        // Sort the array to account for the non-eav attrs being added in
        asort($options);

        return $options;
    }
}
