<?php

class Bronto_Common_Model_System_Config_Source_Related
{

    /**
     * @var array
     */
    protected $_options;

    /**
     * Related Product Sources key => value pairs
     *
     * @param null $isMultiselect
     *
     * @return array
     */
    public function toOptionArray($isMultiselect = null)
    {
        if (!is_null($this->_options)) {
            return $this->_options;
        }

        $helper         = Mage::helper('bronto_common');

        $productSources = array(
            array('value' => 'related', 'label' => $helper->__('Related Products')),
            array('value' => 'upsell', 'label' => $helper->__('Up-sells')),
            array('value' => 'crosssell', 'label' => $helper->__('Cross-sells')),
        );

        $globalSources = array(
            array('value' => 'bestseller', 'label' => $helper->__('Bestsellers')),
            array('value' => 'mostviewed', 'label' => $helper->__('Most Viewed')),
        );

        $customerSources = array(
            array('value' => 'recentlyviewed', 'label' => $helper->__('Recently Viewed')),
        );

        $this->_options = array(
            array('label' => $helper->__('Product Specific Sources'), 'value' => $productSources),
            array('label' => $helper->__('Global Sources'), 'value' => $globalSources),
            array('label' => $helper->__('Customer Specific Sources'), 'value' => $customerSources),
        );

        if (!$isMultiselect) {
            array_unshift($this->_options, array('value' => '', 'label' => $helper->__('--Please Select--')));
        }

        return $this->_options;
    }
}
