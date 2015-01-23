<?php

class Bronto_Common_Model_System_Config_Source_Image
{
    protected $_helper;

    /**
     * Return product image types
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->_helper = Mage::helper('bronto_common');

        return array(
            'image'       => $this->_helper->__('Base Image'),
            'small_image' => $this->_helper->__('Small Image'),
            'thumbnail'   => $this->_helper->__('Thumbnail'),
        );
    }
}
