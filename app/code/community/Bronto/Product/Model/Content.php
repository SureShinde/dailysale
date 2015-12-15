<?php

class Bronto_Product_Model_Content
{
    protected $_options;

    /**
     * Gets the sources for the content types
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!is_null($this->_options)) {
            return $this->_options;
        }

        $helper = Mage::helper('bronto_product');
        $this->_options = array(
            'api' => $helper->__('Email Based'),
            'content_tag' => $helper->__('Content Tag'),
        );
        return $this->_options;
    }
}
