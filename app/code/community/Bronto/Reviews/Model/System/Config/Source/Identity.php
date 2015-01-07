<?php

class Bronto_Reviews_Model_System_Config_Source_Identity
{
    protected $_options;

    /**
     * Options for the Bronto Review email selector
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!empty($this->_options)) {
            return $this->_options;
        }
        $ident = Mage::getModel('adminhtml/system_config_source_email_identity')->toOptionArray();
        array_unshift($ident, array(
            'value' => 'custom',
            'label' => Mage::helper('bronto_reviews')->__('-- Configure Email --')
        ));
        $this->_options = $ident;
        return $this->_options;
    }
}
