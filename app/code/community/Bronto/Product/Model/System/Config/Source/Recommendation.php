<?php

class Bronto_Product_Model_System_Config_Source_Recommendation
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
    public function toOptionArray($isMultiselect = null, $fallback = false)
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
            array('value' => 'new', 'label' => $helper->__('New Products')),
            array('value' => 'bestseller', 'label' => $helper->__('Bestsellers')),
            array('value' => 'mostviewed', 'label' => $helper->__('Most Viewed by last 30 days')),
        );

        $customerSources = array(
            array('value' => 'recentlyviewed', 'label' => $helper->__('Recently Viewed')),
        );

        $customSources = array(
            array('value' => 'custom', 'label' => $helper->__('Custom Products'))
        );

        $this->_options = array();
        if (!$fallback) {
            $this->_options[] = array('label' => $helper->__('Product Sources'), 'value' => $productSources);
        }

        $this->_options[] = array('label' => $helper->__('Global Sources'), 'value' => $globalSources);
        $this->_options[] = array('label' => $helper->__('Customer Sources'), 'value' => $customerSources);
        $this->_options[] = array('label' => $helper->__('Manual Entry'), 'value' => $customSources);

        if (!$isMultiselect) {
            array_unshift($this->_options, array('value' => '', 'label' => $helper->__('-- None Selected --')));
        }

        return $this->_options;
    }
}
