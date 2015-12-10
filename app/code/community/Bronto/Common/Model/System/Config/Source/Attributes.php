<?php

class Bronto_Common_Model_System_Config_Source_Attributes
{
    private $_options;
    // Exclude a set of base product attributes that are already included
    private $_exclude = array(
        'price',
        'group_price',
        'special_price',
        'special_price_from_date',
        'special_price_to_date',
        'tier_price',
        'description',
        'short_description',
        'sku',
        'name',
        'image',
        'image_label',
        'small_image',
        'small_image_label',
        'thumbnail',
        'thumbnail_label',
    );

    public function toOptionArray()
    {
        if (is_null($this->_options)) {
            $this->_options = array(
                '' => Mage::helper('bronto_common')->__('-- None Selected --')
            );
            $attrs = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addVisibleFilter()
                ->addFieldToFilter('main_table.attribute_code', array('nin' => $this->_exclude));
            foreach ($attrs as $attr) {
                if (!$attr->getFrontendLabel()) {
                    continue;
                }
                $this->_options[$attr->getAttributeCode()] = $attr->getFrontendLabel();
            }
        }
        return $this->_options;
    }
}
